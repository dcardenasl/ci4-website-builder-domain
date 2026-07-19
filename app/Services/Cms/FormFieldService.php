<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\FormFieldCreateRequestDTO;
use App\DTO\Request\Cms\FormFieldReorderRequestDTO;
use App\DTO\Request\Cms\FormFieldUpdateRequestDTO;
use App\DTO\Response\Cms\FormFieldResponseDTO;
use App\Entities\FormFieldEntity;
use App\Libraries\Cms\CacheInvalidationClient;
use App\Libraries\Cms\FormOptionLabelsCodec;
use App\Libraries\Cms\ModelResultNormalizer;
use App\Libraries\Cms\TranslationSynchronizer;
use App\Models\FormFieldModel;
use App\Models\FormFieldTranslationModel;
use App\Models\FormModel;
use CodeIgniter\Database\BaseConnection;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

/**
 * Owns the full lifecycle of a form's fields: CRUD, reordering, translation
 * persistence, and option-label sanitization/pruning. Split out of FormService
 * (2026-07-19) so form-level concerns (the form itself, its usage report) and
 * field-level concerns (a sub-entity with its own validation and translation
 * shape) don't share one 900-line class.
 */
class FormFieldService
{
    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    public function __construct(
        private FormFieldModel $fieldModel,
        private FormFieldTranslationModel $fieldTranslationModel,
        private CacheInvalidationClient $cacheInvalidator,
        private BaseConnection $db,
        private ?TranslationSynchronizer $translationSynchronizer = null,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(int $formId): array
    {
        $this->requireForm($formId);

        return array_map(
            fn (array $fieldData) => FormFieldResponseDTO::fromArray($fieldData)->toArray(),
            $this->listWithTranslations($formId)
        );
    }

    /**
     * Raw (non-DTO) field data with translations, ordered by display_order.
     * Used both by list() above and by FormService to assemble the parent
     * form's `fields` payload.
     *
     * @return list<array<string, mixed>>
     */
    public function listWithTranslations(int $formId): array
    {
        /** @var list<FormFieldEntity> $fields */
        $fields = $this->fieldModel
            ->where('form_id', $formId)
            ->orderBy('display_order', 'ASC')
            ->findAll();

        return array_map(
            fn (FormFieldEntity $f) => $this->withFieldTranslations($f->toArray()),
            $fields
        );
    }

    public function get(int $fieldId): FormFieldResponseDTO
    {
        /** @var FormFieldEntity|null */
        $field = $this->fieldModel->find($fieldId);
        if ($field === null) {
            throw new NotFoundException(lang('Forms.field_not_found'));
        }

        return FormFieldResponseDTO::fromArray($this->withFieldTranslations($field->toArray()));
    }

    public function create(int $formId, FormFieldCreateRequestDTO $dto): FormFieldResponseDTO
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

        return $this->get($fieldId);
    }

    public function update(int $formId, int $fieldId, FormFieldUpdateRequestDTO $dto): FormFieldResponseDTO
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

        return $this->get($fieldId);
    }

    public function delete(int $formId, int $fieldId): void
    {
        $this->requireField($formId, $fieldId);
        $this->fieldModel->delete($fieldId);
        $this->cacheInvalidator->invalidate(['forms']);
    }

    public function reorder(int $formId, FormFieldReorderRequestDTO $dto): void
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

    /**
     * @param array<string, mixed> $fieldData
     * @return array<string, mixed>
     */
    private function withFieldTranslations(array $fieldData): array
    {
        $translations = $this->fieldTranslationModel
            ->where('form_field_id', $fieldData['id'])
            ->findAll();

        $normalized = ModelResultNormalizer::toArrayList($translations);
        foreach ($normalized as &$row) {
            $row['option_labels'] = FormOptionLabelsCodec::decode($row['option_labels'] ?? null);
        }
        unset($row);

        $fieldData['translations'] = $normalized;

        return $fieldData;
    }

    /**
     * @param array<int|string, mixed> $translations
     */
    private function saveFieldTranslations(int $fieldId, array $translations): void
    {
        $rows = [];
        foreach ($translations as $trans) {
            if (! is_array($trans)) {
                continue;
            }
            $languageId = (int) ($trans['language_id'] ?? 0);
            if ($languageId === 0) {
                continue;
            }

            $optionLabels = isset($trans['option_labels']) && is_array($trans['option_labels'])
                ? $this->sanitizeOptionLabels($trans['option_labels'])
                : [];

            $rows[] = [
                'language_id'    => $languageId,
                'label'          => (string) ($trans['label'] ?? ''),
                'placeholder'    => isset($trans['placeholder']) && $trans['placeholder'] !== '' ? (string) $trans['placeholder'] : null,
                'help_text'      => isset($trans['help_text']) && $trans['help_text'] !== '' ? (string) $trans['help_text'] : null,
                'option_labels'  => $optionLabels !== [] ? json_encode($optionLabels, JSON_UNESCAPED_UNICODE) : null,
                'error_required' => isset($trans['error_required']) && $trans['error_required'] !== '' ? (string) $trans['error_required'] : null,
                'error_invalid'  => isset($trans['error_invalid']) && $trans['error_invalid'] !== '' ? (string) $trans['error_invalid'] : null,
            ];
        }

        ($this->translationSynchronizer ?? throw new \LogicException(lang('Api.translationSynchronizerRequired')))->replace(
            $this->fieldTranslationModel,
            'form_field_id',
            $fieldId,
            $rows,
            static fn (array $row): array => $row,
        );
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

            $decoded = FormOptionLabelsCodec::decode($trans['option_labels'] ?? null);
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

    private function requireForm(int $formId): void
    {
        /** @var FormModel $formModel */
        $formModel = model(FormModel::class);
        if ($formModel->find($formId) === null) {
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
