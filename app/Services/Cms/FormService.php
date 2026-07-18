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
use App\Libraries\Cms\CacheInvalidationClient;
use App\Models\FormFieldModel;
use App\Models\FormFieldTranslationModel;
use App\Models\FormModel;
use App\Models\FormTranslationModel;
use CodeIgniter\Database\BaseConnection;
use dcardenasl\Ci4ApiCore\Exceptions\ConflictException;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

class FormService
{
    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    public function __construct(
        private FormModel $formModel,
        private FormTranslationModel $translationModel,
        private FormFieldModel $fieldModel,
        private FormFieldTranslationModel $fieldTranslationModel,
        private CacheInvalidationClient $cacheInvalidator,
        private BaseConnection $db,
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

    public function get(int $id, ?string $locale = null): FormResponseDTO
    {
        /** @var FormEntity|null */
        $form = $this->formModel->find($id);
        if ($form === null) {
            throw new NotFoundException(lang('Forms.not_found'));
        }

        return $this->buildFormResponse($form->toArray(), $locale);
    }

    public function getByKey(string $formKey, ?string $locale = null): FormResponseDTO
    {
        /** @var FormEntity|null */
        $form = $this->formModel->where('form_key', $formKey)->first();
        if ($form === null) {
            throw new NotFoundException(lang('Forms.not_found'));
        }

        return $this->buildFormResponse($form->toArray(), $locale);
    }

    public function create(FormCreateRequestDTO $dto): FormResponseDTO
    {
        $existing = $this->formModel->where('form_key', $dto->form_key)->first();
        if ($existing !== null) {
            throw new ValidationException(lang('Forms.duplicate_form_key'), ['form_key' => lang('Forms.duplicate_form_key')]);
        }

        $this->db->transStart();

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

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException(lang('Forms.create_failed_db'));
        }

        $this->cacheInvalidator->invalidate(['forms']);

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

        $this->db->transStart();

        if ($fields !== []) {
            $this->formModel->update($id, $fields);
        }

        if ($dto->translations !== []) {
            $this->saveTranslations($id, $dto->translations);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException(lang('Forms.update_failed_db'));
        }

        $this->cacheInvalidator->invalidate(['forms']);

        return $this->get($id);
    }

    public function delete(int $id): void
    {
        /** @var FormEntity|null */
        $form = $this->formModel->find($id);
        if ($form === null) {
            throw new NotFoundException(lang('Forms.not_found'));
        }

        $usages = $this->getUsages($id);
        if ($usages !== []) {
            $descriptions = array_map(
                fn (array $usage): string => $this->describeUsage($usage),
                $usages
            );

            throw new ConflictException(
                lang('Forms.in_use', [(string) count($usages), implode('; ', $descriptions)])
            );
        }

        $hasSubmissions = (int) $this->db
            ->table('cms_form_submissions')
            ->where('form_id', $id)
            ->countAllResults() > 0;

        if ($hasSubmissions) {
            $this->formModel->update($id, ['is_active' => false]);
            $this->cacheInvalidator->invalidate(['forms']);
            return;
        }

        $this->formModel->delete($id);
        $this->cacheInvalidator->invalidate(['forms']);
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

        $this->db->transStart();

        $this->fieldModel->insert([
            'form_id'       => $formId,
            'field_key'     => $dto->field_key,
            'field_type'    => $dto->field_type,
            'options'       => $dto->options !== null ? json_encode($dto->options, JSON_UNESCAPED_UNICODE) : null,
            'display_order' => $dto->display_order,
            'is_required'   => $dto->is_required,
            'is_active'     => $dto->is_active,
        ]);
        $fieldId = (int) $this->fieldModel->getInsertID();

        $this->saveFieldTranslations($fieldId, $dto->translations);
        $this->pruneOptionLabels($fieldId);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException(lang('Forms.field_create_failed_db'));
        }

        $this->cacheInvalidator->invalidate(['forms']);

        return $this->getField($fieldId);
    }

    public function updateField(int $formId, int $fieldId, FormFieldUpdateRequestDTO $dto): FormFieldResponseDTO
    {
        $this->requireField($formId, $fieldId);

        $fields = $dto->toArray();
        unset($fields['translations']);
        if (array_key_exists('options', $fields)) {
            $fields['options'] = $fields['options'] !== null ? json_encode($fields['options'], JSON_UNESCAPED_UNICODE) : null;
        }

        $this->db->transStart();

        if ($fields !== []) {
            $this->fieldModel->update($fieldId, $fields);
        }

        if ($dto->translations !== []) {
            $this->saveFieldTranslations($fieldId, $dto->translations);
        }

        // Every save re-derives valid labels from the field's CURRENT options,
        // dropping any option_labels entries for values that no longer exist
        // (removed options, or a value edited/regenerated to something else).
        // Otherwise those orphaned entries accumulate silently forever.
        $this->pruneOptionLabels($fieldId);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException(lang('Forms.field_update_failed_db'));
        }

        $this->cacheInvalidator->invalidate(['forms']);

        return $this->getField($fieldId);
    }

    public function deleteField(int $formId, int $fieldId): void
    {
        $this->requireField($formId, $fieldId);
        $this->fieldModel->delete($fieldId);
        $this->cacheInvalidator->invalidate(['forms']);
    }

    public function reorderFields(int $formId, FormFieldReorderRequestDTO $dto): void
    {
        $this->requireForm($formId);

        $this->db->transStart();

        foreach ($dto->ordered_ids as $order => $fieldId) {
            $this->fieldModel
                ->where('id', $fieldId)
                ->where('form_id', $formId)
                ->set('display_order', $order)
                ->update();
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException(lang('Forms.field_reorder_failed_db'));
        }

        $this->cacheInvalidator->invalidate(['forms']);
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

            // Options are stored as stable, language-independent values on the
            // field; their display labels are per-language, in this locale's
            // translation row. Combine them here — form_embed.php on the web
            // side only ever sees the resolved {value,label} shape.
            $optionValues = is_array($fieldData['options'] ?? null) ? $fieldData['options'] : [];
            $optionLabels = $this->decodeOptionLabels($fieldTrans['option_labels'] ?? null);
            $resolvedOptions = array_map(
                static fn (string $value): array => ['value' => $value, 'label' => $optionLabels[$value] ?? $value],
                $optionValues
            );

            return [
                'field_key'     => $fieldData['field_key'],
                'field_type'    => $fieldData['field_type'],
                'options'       => $resolvedOptions,
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
    private function buildFormResponse(array $formData, ?string $locale = null): FormResponseDTO
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
        $formData['usages']       = $this->getUsages((int) $formData['id'], $locale);

        return FormResponseDTO::fromArray($formData);
    }

    /**
     * @return list<array{resource: string, resource_id: int, role: string, label: string|null, context: array{owner_type: string, owner_id: int, block_key: string, block_name: string|null}}>
     */
    public function getUsages(int $formId, ?string $locale = null): array
    {
        /** @var FormEntity|null */
        $form = $this->formModel->find($formId);
        if ($form === null) {
            throw new NotFoundException(lang('Forms.not_found'));
        }

        $formKey = (string) $form->form_key;
        $result = $this->db->table('cms_block_instances bi')
            ->select('bi.id, bi.owner_type, bi.owner_id, bi.sort_order, cb.block_key, cb.name as block_name')
            ->join('cms_content_blocks cb', 'cb.id = bi.block_id')
            ->where('cb.block_key', 'form_embed')
            ->where("JSON_UNQUOTE(JSON_EXTRACT(bi.block_config, '$.form_key'))", $formKey)
            ->orderBy('bi.owner_type', 'ASC')
            ->orderBy('bi.owner_id', 'ASC')
            ->orderBy('bi.sort_order', 'ASC')
            ->orderBy('bi.id', 'ASC')
            ->get();
        $rows = $result ? array_values($result->getResultArray()) : [];

        $defaultLanguageId = $this->resolveLanguageId(null);
        $languageId = is_string($locale) && trim($locale) !== ''
            ? $this->resolveLanguageId($locale)
            : $defaultLanguageId;
        $ownerTitles = $this->resolveOwnerTitles($rows, $languageId, $defaultLanguageId);

        return array_values(array_map(function (array $row) use ($ownerTitles): array {
            $ownerType = (string) ($row['owner_type'] ?? '');
            $ownerId   = (int) ($row['owner_id'] ?? 0);
            $title     = $ownerTitles[$ownerType . ':' . $ownerId] ?? null;

            return [
                'resource'    => 'block_instances',
                'resource_id' => (int) ($row['id'] ?? 0),
                'role'        => $ownerType,
                'label'       => $title !== null && $title !== '' ? $title : sprintf('%s #%d', $ownerType, $ownerId),
                'context'     => [
                    'owner_type' => $ownerType,
                    'owner_id'   => $ownerId,
                    'block_key'  => (string) ($row['block_key'] ?? ''),
                    'block_name' => isset($row['block_name']) ? (string) $row['block_name'] : null,
                ],
            ];
        }, $rows));
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

        $normalized = $this->normalizeRows($translations);
        foreach ($normalized as &$row) {
            $row['option_labels'] = $this->decodeOptionLabels($row['option_labels'] ?? null);
        }
        unset($row);

        $fieldData['translations'] = $normalized;

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

    private function resolveLanguageId(?string $locale): ?int
    {
        if (is_string($locale) && trim($locale) !== '') {
            $result = $this->db->table('cms_languages')
                ->select('id')
                ->where('code', trim($locale))
                ->get();
            $row = $result ? $result->getRowArray() : null;

            if (is_array($row) && isset($row['id'])) {
                return (int) $row['id'];
            }
        }

        $result = $this->db->table('cms_languages')
            ->select('id')
            ->where('is_default', 1)
            ->get();
        $row = $result ? $result->getRowArray() : null;

        if (is_array($row) && isset($row['id'])) {
            return (int) $row['id'];
        }

        $result = $this->db->table('cms_languages')
            ->select('id')
            ->orderBy('id', 'ASC')
            ->limit(1)
            ->get();
        $row = $result ? $result->getRowArray() : null;

        return is_array($row) && isset($row['id']) ? (int) $row['id'] : null;
    }

    /**
     * Resolve all usage labels with at most one translation query per owner
     * type. This avoids an N+1 query each time a popular form is inspected.
     *
     * @param list<array<string, mixed>> $usageRows
     * @return array<string, string>
     */
    private function resolveOwnerTitles(array $usageRows, ?int $preferredLanguageId, ?int $fallbackLanguageId): array
    {
        $languagePriority = array_values(array_unique(array_filter(
            [$preferredLanguageId, $fallbackLanguageId],
            static fn (?int $languageId): bool => $languageId !== null && $languageId > 0
        )));
        $titles = [];

        foreach ([
            'page' => ['table' => 'cms_page_translations', 'fk' => 'page_id'],
            'entry' => ['table' => 'cms_entry_translations', 'fk' => 'entry_id'],
        ] as $ownerType => $definition) {
            $ownerIds = [];
            foreach ($usageRows as $usageRow) {
                if (($usageRow['owner_type'] ?? null) !== $ownerType) {
                    continue;
                }

                $ownerId = (int) ($usageRow['owner_id'] ?? 0);
                if ($ownerId > 0) {
                    $ownerIds[] = $ownerId;
                }
            }

            $ownerIds = array_values(array_unique($ownerIds));
            if ($ownerIds === []) {
                continue;
            }

            $result = $this->db->table($definition['table'])
                ->select($definition['fk'] . ' as owner_id, language_id, title')
                ->whereIn($definition['fk'], $ownerIds)
                ->orderBy('language_id', 'ASC')
                ->get();
            $translationRows = $result ? $result->getResultArray() : [];

            /** @var array<int, array<int, string>> $byOwnerAndLanguage */
            $byOwnerAndLanguage = [];
            foreach ($translationRows as $translationRow) {
                $ownerId = (int) ($translationRow['owner_id'] ?? 0);
                $languageId = (int) ($translationRow['language_id'] ?? 0);
                $title = trim((string) ($translationRow['title'] ?? ''));
                if ($ownerId > 0 && $languageId > 0 && $title !== '') {
                    $byOwnerAndLanguage[$ownerId][$languageId] = $title;
                }
            }

            foreach ($ownerIds as $ownerId) {
                $available = $byOwnerAndLanguage[$ownerId] ?? [];
                foreach ($languagePriority as $languageId) {
                    if (isset($available[$languageId])) {
                        $titles[$ownerType . ':' . $ownerId] = $available[$languageId];
                        continue 2;
                    }
                }

                $firstTitle = reset($available);
                if (is_string($firstTitle) && $firstTitle !== '') {
                    $titles[$ownerType . ':' . $ownerId] = $firstTitle;
                }
            }
        }

        return $titles;
    }

    /**
     * @param array{resource: string, resource_id: int, role: string, label: string|null, context: array{owner_type: string, owner_id: int, block_key: string, block_name: string|null}} $usage
     */
    private function describeUsage(array $usage): string
    {
        $ownerType = $usage['context']['owner_type'];
        $ownerId   = $usage['context']['owner_id'];
        $instance  = $usage['resource_id'];
        $blockKey  = $usage['context']['block_key'];
        $blockName = $usage['context']['block_name'] ?? null;
        $title     = $usage['label'];

        $label = $ownerType === 'page' ? lang('Forms.usage_page') : lang('Forms.usage_entry');
        $block = trim((string) $blockName) !== ''
            ? (string) $blockName
            : (trim($blockKey) !== '' ? $blockKey : lang('Forms.usage_instance'));

        return $title !== null
            ? sprintf('%s "%s" (id %d, %s %s #%d)', $label, $title, $ownerId, lang('Forms.usage_instance'), $block, $instance)
            : sprintf('%s (id %d, %s %s #%d)', $label, $ownerId, lang('Forms.usage_instance'), $block, $instance);
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

            $optionLabels = isset($trans['option_labels']) && is_array($trans['option_labels'])
                ? $this->sanitizeOptionLabels($trans['option_labels'])
                : [];

            $payload = [
                'label'          => (string) ($trans['label'] ?? ''),
                'placeholder'    => isset($trans['placeholder']) && $trans['placeholder'] !== '' ? (string) $trans['placeholder'] : null,
                'help_text'      => isset($trans['help_text']) && $trans['help_text'] !== '' ? (string) $trans['help_text'] : null,
                'option_labels'  => $optionLabels !== [] ? json_encode($optionLabels, JSON_UNESCAPED_UNICODE) : null,
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
     * Drops option_labels entries for values that no longer exist on the
     * field — e.g. an option was removed or its value edited/regenerated to
     * something else. Runs after every field save so stale entries never
     * accumulate.
     */
    private function pruneOptionLabels(int $fieldId): void
    {
        /** @var FormFieldEntity|null $field */
        $field = $this->fieldModel->find($fieldId);
        if ($field === null) {
            return;
        }

        $fieldData   = $field->toArray();
        $validValues = is_array($fieldData['options'] ?? null) ? $fieldData['options'] : [];
        $validLookup = array_flip($validValues);

        $translations = $this->fieldTranslationModel->where('form_field_id', $fieldId)->findAll();
        foreach ($translations as $trans) {
            if (! is_array($trans)) {
                continue;
            }

            $decoded = $this->decodeOptionLabels($trans['option_labels'] ?? null);
            if ($decoded === []) {
                continue;
            }

            $pruned = array_intersect_key($decoded, $validLookup);
            if ($pruned === $decoded) {
                continue;
            }

            $this->fieldTranslationModel
                ->where('id', is_scalar($trans['id'] ?? null) ? (int) $trans['id'] : 0)
                ->set('option_labels', $pruned !== [] ? json_encode($pruned, JSON_UNESCAPED_UNICODE) : null)
                ->update();
        }
    }

    /**
     * @param array<int|string, mixed> $raw
     * @return array<string, string>
     */
    private function sanitizeOptionLabels(array $raw): array
    {
        $clean = [];
        foreach ($raw as $value => $label) {
            $value = trim((string) $value);
            $label = trim((string) $label);
            if ($value === '' || $label === '') {
                continue;
            }
            $clean[$value] = $label;
        }

        return $clean;
    }

    /**
     * @return array<string, string>
     */
    private function decodeOptionLabels(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
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

        // (array) $entity does NOT call Entity::toArray() — it exposes the
        // object's raw properties with PHP's mangled protected/private-property
        // keys (e.g. "\0*\0attributes"), never a plain 'id' key. That silently
        // made $languageId always 0 below, so the language_id lookup never
        // matched and every call fell through to "first translation for this
        // row" — which is why every locale rendered the same (first-created)
        // language regardless of what was requested.
        /** @var array<string, mixed> $langArr */
        $langArr    = is_array($language) ? $language : $language->toArray();
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

        // See resolveFormTranslation() above — (array) $entity doesn't work here.
        /** @var array<string, mixed> $langArr */
        $langArr    = is_array($language) ? $language : $language->toArray();
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
