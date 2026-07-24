<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Response\Cms\FormPublicDefinitionResponseDTO;
use App\Entities\FormEntity;
use App\Entities\FormFieldEntity;
use App\Libraries\Cms\FormOptionLabelsCodec;
use App\Models\FormFieldModel;
use App\Models\FormFieldTranslationModel;
use App\Models\FormModel;
use App\Models\FormTranslationModel;
use App\Models\LanguageModel;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;

/**
 * Assembles the public-facing form definition (fields + resolved translations
 * for one locale) served to the website's form_embed block. Split out of
 * FormService (2026-07-19) — this is a read-model assembler for an anonymous,
 * unauthenticated audience, the same role PublicEntryReader plays for entries,
 * and has no overlap with the admin CRUD lifecycle in FormService/FormFieldService.
 */
class FormPublicDefinitionAssembler
{
    public function __construct(
        private FormModel $formModel,
        private FormTranslationModel $translationModel,
        private FormFieldModel $fieldModel,
        private FormFieldTranslationModel $fieldTranslationModel,
    ) {
    }

    public function getDefinition(string $lang, string $formKey): FormPublicDefinitionResponseDTO
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
            $optionLabels = FormOptionLabelsCodec::decode($fieldTrans['option_labels'] ?? null);
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

    /**
     * @return array<string, mixed>
     */
    private function resolveFormTranslation(int $formId, string $lang): array
    {
        /** @var LanguageModel $languageModel */
        $languageModel = model(LanguageModel::class);

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
    private function resolveFieldTranslation(int $fieldId, string $lang): array
    {
        /** @var LanguageModel $languageModel */
        $languageModel = model(LanguageModel::class);

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
}
