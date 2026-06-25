<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\FormCreateRequestDTO;
use App\DTO\Request\Cms\FormFieldCreateRequestDTO;
use App\DTO\Request\Cms\FormFieldReorderRequestDTO;
use App\DTO\Request\Cms\FormFieldUpdateRequestDTO;
use App\DTO\Request\Cms\FormUpdateRequestDTO;
use App\DTO\Response\Cms\FormFieldResponseDTO;
use App\DTO\Response\Cms\FormPublicDefinitionResponseDTO;
use App\DTO\Response\Cms\FormResponseDTO;
use App\Entities\FormEntity;
use App\Entities\FormFieldEntity;
use App\Models\FormFieldModel;
use App\Models\FormFieldTranslationModel;
use App\Models\FormModel;
use App\Models\FormTranslationModel;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

class FormService
{
    public function __construct(
        private FormModel $formModel,
        private FormTranslationModel $translationModel,
        private FormFieldModel $fieldModel,
        private FormFieldTranslationModel $fieldTranslationModel,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        /** @var list<FormEntity> */
        $forms = $this->formModel->orderBy('form_key', 'ASC')->findAll();

        return array_map(function (FormEntity $form) {
            $data   = $form->toArray();
            $trans  = $this->translationModel->where('form_id', $data['id'])->findAll();
            $fields = $this->fieldModel->where('form_id', $data['id'])->orderBy('display_order', 'ASC')->findAll();

            $data['translations'] = $this->normalizeRows($trans);
            /** @var list<FormFieldEntity> $typedFields */
            $typedFields          = $fields;
            $data['fields']       = array_map(
                fn (FormFieldEntity $f) => $this->withFieldTranslations($f->toArray()),
                $typedFields
            );

            return FormResponseDTO::fromArray($data)->toArray();
        }, $forms);
    }

    public function get(int $id): FormResponseDTO
    {
        /** @var FormEntity|null */
        $form = $this->formModel->find($id);
        if ($form === null) {
            throw new NotFoundException(lang('Forms.not_found'));
        }

        return $this->buildFormResponse($form->toArray());
    }

    public function getByKey(string $formKey): FormResponseDTO
    {
        /** @var FormEntity|null */
        $form = $this->formModel->where('form_key', $formKey)->first();
        if ($form === null) {
            throw new NotFoundException(lang('Forms.not_found'));
        }

        return $this->buildFormResponse($form->toArray());
    }

    public function create(FormCreateRequestDTO $dto): FormResponseDTO
    {
        $existing = $this->formModel->where('form_key', $dto->form_key)->first();
        if ($existing !== null) {
            throw new ValidationException(lang('Forms.duplicate_form_key'), ['form_key' => lang('Forms.duplicate_form_key')]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $this->formModel->insert([
            'form_key'              => $dto->form_key,
            'is_active'             => $dto->is_active,
            'has_captcha'           => $dto->has_captcha,
            'notify_email'          => $dto->notify_email,
            'autoreply_enabled'     => $dto->autoreply_enabled,
            'autoreply_email_field' => $dto->autoreply_email_field,
        ]);
        $formId = (int) $this->formModel->getInsertID();

        $this->saveTranslations($formId, $dto->translations);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException(lang('Forms.create_failed_db'));
        }

        service('cacheInvalidationClient')->invalidate(['forms']);

        return $this->get($formId);
    }

    public function update(int $id, FormUpdateRequestDTO $dto): FormResponseDTO
    {
        /** @var FormEntity|null */
        $form = $this->formModel->find($id);
        if ($form === null) {
            throw new NotFoundException(lang('Forms.not_found'));
        }

        $fields = [];
        if ($dto->is_active !== null) {
            $fields['is_active'] = $dto->is_active;
        }
        if ($dto->has_captcha !== null) {
            $fields['has_captcha'] = $dto->has_captcha;
        }
        if (array_key_exists('notify_email', $dto->toArray())) {
            $fields['notify_email'] = $dto->notify_email;
        }
        if ($dto->autoreply_enabled !== null) {
            $fields['autoreply_enabled'] = $dto->autoreply_enabled;
        }
        if (array_key_exists('autoreply_email_field', $dto->toArray())) {
            $fields['autoreply_email_field'] = $dto->autoreply_email_field;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        if ($fields !== []) {
            $this->formModel->update($id, $fields);
        }

        if ($dto->translations !== []) {
            $this->saveTranslations($id, $dto->translations);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException(lang('Forms.update_failed_db'));
        }

        service('cacheInvalidationClient')->invalidate(['forms']);

        return $this->get($id);
    }

    public function delete(int $id): void
    {
        /** @var FormEntity|null */
        $form = $this->formModel->find($id);
        if ($form === null) {
            throw new NotFoundException(lang('Forms.not_found'));
        }

        $hasSubmissions = (int) \Config\Database::connect()
            ->table('cms_form_submissions')
            ->where('form_id', $id)
            ->countAllResults() > 0;

        if ($hasSubmissions) {
            $this->formModel->update($id, ['is_active' => false]);
            service('cacheInvalidationClient')->invalidate(['forms']);
            return;
        }

        $this->formModel->delete($id);
        service('cacheInvalidationClient')->invalidate(['forms']);
    }

    // ── Field Management ────────────────────────────────────────────────────

    /**
     * @return list<array<string, mixed>>
     */
    public function listFields(int $formId): array
    {
        $this->requireForm($formId);

        /** @var list<FormFieldEntity> */
        $fields = $this->fieldModel
            ->where('form_id', $formId)
            ->orderBy('display_order', 'ASC')
            ->findAll();

        return array_map(
            fn (FormFieldEntity $f) => FormFieldResponseDTO::fromArray($this->withFieldTranslations($f->toArray()))->toArray(),
            $fields
        );
    }

    public function createField(int $formId, FormFieldCreateRequestDTO $dto): FormFieldResponseDTO
    {
        $this->requireForm($formId);

        $existing = $this->fieldModel
            ->where('form_id', $formId)
            ->where('field_key', $dto->field_key)
            ->first();

        if ($existing !== null) {
            throw new ValidationException(lang('Forms.duplicate_field_key'), ['field_key' => lang('Forms.duplicate_field_key')]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $this->fieldModel->insert([
            'form_id'       => $formId,
            'field_key'     => $dto->field_key,
            'field_type'    => $dto->field_type,
            'display_order' => $dto->display_order,
            'is_required'   => $dto->is_required,
            'is_active'     => $dto->is_active,
        ]);
        $fieldId = (int) $this->fieldModel->getInsertID();

        $this->saveFieldTranslations($fieldId, $dto->translations);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException(lang('Forms.field_create_failed_db'));
        }

        service('cacheInvalidationClient')->invalidate(['forms']);

        return $this->getField($fieldId);
    }

    public function updateField(int $formId, int $fieldId, FormFieldUpdateRequestDTO $dto): FormFieldResponseDTO
    {
        $this->requireField($formId, $fieldId);

        $fields = $dto->toArray();
        unset($fields['translations']);

        $db = \Config\Database::connect();
        $db->transStart();

        if ($fields !== []) {
            $this->fieldModel->update($fieldId, $fields);
        }

        if ($dto->translations !== []) {
            $this->saveFieldTranslations($fieldId, $dto->translations);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException(lang('Forms.field_update_failed_db'));
        }

        service('cacheInvalidationClient')->invalidate(['forms']);

        return $this->getField($fieldId);
    }

    public function deleteField(int $formId, int $fieldId): void
    {
        $this->requireField($formId, $fieldId);
        $this->fieldModel->delete($fieldId);
        service('cacheInvalidationClient')->invalidate(['forms']);
    }

    public function reorderFields(int $formId, FormFieldReorderRequestDTO $dto): void
    {
        $this->requireForm($formId);

        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($dto->ordered_ids as $order => $fieldId) {
            $this->fieldModel
                ->where('id', $fieldId)
                ->where('form_id', $formId)
                ->set('display_order', $order)
                ->update();
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException(lang('Forms.field_reorder_failed_db'));
        }

        service('cacheInvalidationClient')->invalidate(['forms']);
    }

    // ── Public form definition ───────────────────────────────────────────────

    public function getPublicDefinition(string $lang, string $formKey): FormPublicDefinitionResponseDTO
    {
        /** @var FormEntity|null */
        $form = $this->formModel
            ->where('form_key', $formKey)
            ->where('is_active', 1)
            ->first();

        if ($form === null) {
            throw new NotFoundException(lang('Forms.not_found_or_inactive'));
        }

        $formData = $form->toArray();

        $translation = $this->resolveFormTranslation((int) $formData['id'], $lang);

        /** @var list<FormFieldEntity> */
        $formFields = $this->fieldModel
            ->where('form_id', $formData['id'])
            ->where('is_active', 1)
            ->orderBy('display_order', 'ASC')
            ->findAll();

        $publicFields = array_map(function (FormFieldEntity $field) use ($lang): array {
            $fieldData   = $field->toArray();
            $fieldTrans  = $this->resolveFieldTranslation((int) $fieldData['id'], $lang);

            return [
                'field_key'     => $fieldData['field_key'],
                'field_type'    => $fieldData['field_type'],
                'is_required'   => (bool) $fieldData['is_required'],
                'display_order' => (int) $fieldData['display_order'],
                'label'         => $fieldTrans['label'] ?? $fieldData['field_key'],
                'placeholder'   => $fieldTrans['placeholder'] ?? null,
                'help_text'     => $fieldTrans['help_text'] ?? null,
                'error_required' => $fieldTrans['error_required'] ?? null,
                'error_invalid'  => $fieldTrans['error_invalid'] ?? null,
            ];
        }, $formFields);

        $definitionData = array_merge($formData, $translation, ['fields' => $publicFields]);

        return FormPublicDefinitionResponseDTO::fromArray($definitionData);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $formData
     */
    private function buildFormResponse(array $formData): FormResponseDTO
    {
        $trans  = $this->translationModel->where('form_id', $formData['id'])->findAll();

        /** @var list<FormFieldEntity> */
        $fields = $this->fieldModel
            ->where('form_id', $formData['id'])
            ->orderBy('display_order', 'ASC')
            ->findAll();

        $formData['translations'] = $this->normalizeRows($trans);
        $formData['fields']       = array_map(
            fn (FormFieldEntity $f) => $this->withFieldTranslations($f->toArray()),
            $fields
        );

        return FormResponseDTO::fromArray($formData);
    }

    public function getField(int $fieldId): FormFieldResponseDTO
    {
        /** @var FormFieldEntity|null */
        $field = $this->fieldModel->find($fieldId);
        if ($field === null) {
            throw new NotFoundException(lang('Forms.field_not_found'));
        }

        return FormFieldResponseDTO::fromArray($this->withFieldTranslations($field->toArray()));
    }

    /**
     * @param array<string, mixed> $fieldData
     * @return array<string, mixed>
     */
    private function withFieldTranslations(array $fieldData): array
    {
        $translations = $this->fieldTranslationModel
            ->where('form_field_id', $fieldData['id'])
            ->findAll();

        $fieldData['translations'] = $this->normalizeRows($translations);

        return $fieldData;
    }

    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    private function normalizeRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $normalized = [];
        foreach ($rows as $row) {
            if ($row instanceof \CodeIgniter\Entity\Entity) {
                $normalized[] = $row->toArray();
                continue;
            }

            if (is_array($row)) {
                $normalized[] = $row;
            }
        }

        return $normalized;
    }

    /**
     * @param array<int|string, mixed> $translations
     */
    private function saveTranslations(int $formId, array $translations): void
    {
        foreach ($translations as $trans) {
            if (! is_array($trans)) {
                continue;
            }
            $languageId = (int) ($trans['language_id'] ?? 0);
            if ($languageId === 0) {
                continue;
            }

            $existing = $this->translationModel
                ->where('form_id', $formId)
                ->where('language_id', $languageId)
                ->first();

            $payload = [
                'name'            => (string) ($trans['name'] ?? ''),
                'description'     => isset($trans['description']) && $trans['description'] !== '' ? (string) $trans['description'] : null,
                'submit_label'    => (string) ($trans['submit_label'] ?? 'Enviar'),
                'success_message' => isset($trans['success_message']) && $trans['success_message'] !== '' ? (string) $trans['success_message'] : null,
                'error_message'   => isset($trans['error_message']) && $trans['error_message'] !== '' ? (string) $trans['error_message'] : null,
            ];

            if ($existing !== null) {
                $this->translationModel
                    ->where('form_id', $formId)
                    ->where('language_id', $languageId)
                    ->set($payload)
                    ->update();
            } else {
                $this->translationModel->insert(array_merge(['form_id' => $formId, 'language_id' => $languageId], $payload));
            }
        }
    }

    /**
     * @param array<int|string, mixed> $translations
     */
    private function saveFieldTranslations(int $fieldId, array $translations): void
    {
        foreach ($translations as $trans) {
            if (! is_array($trans)) {
                continue;
            }
            $languageId = (int) ($trans['language_id'] ?? 0);
            if ($languageId === 0) {
                continue;
            }

            $existing = $this->fieldTranslationModel
                ->where('form_field_id', $fieldId)
                ->where('language_id', $languageId)
                ->first();

            $payload = [
                'label'          => (string) ($trans['label'] ?? ''),
                'placeholder'    => isset($trans['placeholder']) && $trans['placeholder'] !== '' ? (string) $trans['placeholder'] : null,
                'help_text'      => isset($trans['help_text']) && $trans['help_text'] !== '' ? (string) $trans['help_text'] : null,
                'error_required' => isset($trans['error_required']) && $trans['error_required'] !== '' ? (string) $trans['error_required'] : null,
                'error_invalid'  => isset($trans['error_invalid']) && $trans['error_invalid'] !== '' ? (string) $trans['error_invalid'] : null,
            ];

            if ($existing !== null) {
                $this->fieldTranslationModel
                    ->where('form_field_id', $fieldId)
                    ->where('language_id', $languageId)
                    ->set($payload)
                    ->update();
            } else {
                $this->fieldTranslationModel->insert(array_merge(['form_field_id' => $fieldId, 'language_id' => $languageId], $payload));
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    /** @return array<string, mixed> */
    private function resolveFormTranslation(int $formId, string $lang): array
    {
        /** @var \App\Models\LanguageModel $languageModel */
        $languageModel = model(\App\Models\LanguageModel::class);

        $language = $languageModel->where('code', $lang)->first();
        if ($language === null) {
            $language = $languageModel->where('is_default', 1)->first();
        }

        if ($language === null) {
            return ['name' => '', 'submit_label' => 'Enviar'];
        }

        /** @var array<string, mixed> $langArr */
        $langArr    = is_array($language) ? $language : (array) $language;
        $languageId = (int) ($langArr['id'] ?? 0);

        /** @var array<string, mixed>|null $trans */
        $trans = $this->translationModel
            ->where('form_id', $formId)
            ->where('language_id', $languageId)
            ->first();

        if ($trans === null) {
            /** @var array<string, mixed>|null $trans */
            $trans = $this->translationModel->where('form_id', $formId)->first();
        }

        return is_array($trans) ? $trans : ['name' => '', 'submit_label' => 'Enviar'];
    }

    /**
     * @return array<string, mixed>
     */
    /** @return array<string, mixed> */
    private function resolveFieldTranslation(int $fieldId, string $lang): array
    {
        /** @var \App\Models\LanguageModel $languageModel */
        $languageModel = model(\App\Models\LanguageModel::class);

        $language = $languageModel->where('code', $lang)->first();
        if ($language === null) {
            $language = $languageModel->where('is_default', 1)->first();
        }

        if ($language === null) {
            return [];
        }

        /** @var array<string, mixed> $langArr */
        $langArr    = is_array($language) ? $language : (array) $language;
        $languageId = (int) ($langArr['id'] ?? 0);

        /** @var array<string, mixed>|null $trans */
        $trans = $this->fieldTranslationModel
            ->where('form_field_id', $fieldId)
            ->where('language_id', $languageId)
            ->first();

        if ($trans === null) {
            /** @var array<string, mixed>|null $trans */
            $trans = $this->fieldTranslationModel->where('form_field_id', $fieldId)->first();
        }

        return is_array($trans) ? $trans : [];
    }

    private function requireForm(int $formId): void
    {
        if ($this->formModel->find($formId) === null) {
            throw new NotFoundException(lang('Forms.not_found'));
        }
    }

    private function requireField(int $formId, int $fieldId): void
    {
        $field = $this->fieldModel
            ->where('id', $fieldId)
            ->where('form_id', $formId)
            ->first();

        if ($field === null) {
            throw new NotFoundException(lang('Forms.field_not_found'));
        }
    }
}
