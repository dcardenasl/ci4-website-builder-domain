<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\FormCreateRequestDTO;
use App\DTO\Request\Cms\FormUpdateRequestDTO;
use App\DTO\Response\Cms\FormResponseDTO;
use App\Entities\FormEntity;
use App\Libraries\Cms\CacheInvalidationClient;
use App\Libraries\Cms\ModelResultNormalizer;
use App\Libraries\Cms\OwnerUsageResolver;
use App\Libraries\Cms\TranslationSynchronizer;
use App\Models\FormModel;
use App\Models\FormTranslationModel;
use App\Support\AdminListProjectionDecoder;
use CodeIgniter\Database\BaseConnection;
use dcardenasl\Ci4ApiCore\Exceptions\ConflictException;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

/**
 * Owns the Form aggregate root: CRUD and the "who embeds this form" usage
 * report used to block deletion of a form still in use. Field-level CRUD
 * lives in FormFieldService (a sub-entity with its own validation and
 * translation shape); the public, locale-resolved definition lives in
 * FormPublicDefinitionAssembler (a read-model assembler, same role
 * PublicEntryReader plays for entries). Split 2026-07-19 out of a single
 * 900-line class that mixed all three responsibilities.
 */
class FormService
{
    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    public function __construct(
        private FormModel $formModel,
        private FormTranslationModel $translationModel,
        private CacheInvalidationClient $cacheInvalidator,
        private BaseConnection $db,
        private OwnerUsageResolver $ownerUsageResolver,
        private FormFieldService $fieldService,
        private ?TranslationSynchronizer $translationSynchronizer = null,
    ) {
    }

    /**
     * The admin table can request a bounded projection. The no-argument form
     * remains the aggregate read used by existing callers and show/edit flows.
     *
     * @param array<string, mixed> $criteria
     * @return list<array<string, mixed>>|array{data: list<array<string, mixed>>, total: int, page: int, per_page: int}
     */
    public function list(array $criteria = []): array
    {
        if (($criteria['projection'] ?? 'full') === 'list') {
            return $this->listAdminProjection($criteria);
        }

        /** @var list<FormEntity> */
        $forms = $this->formModel->orderBy('form_key', 'ASC')->findAll();

        return array_map(function (FormEntity $form) {
            $data  = $form->toArray();
            $trans = $this->translationModel->where('form_id', $data['id'])->findAll();

            $data['translations'] = ModelResultNormalizer::toArrayList($trans);
            $data['fields']       = $this->fieldService->listWithTranslations((int) $data['id']);

            return FormResponseDTO::fromArray($data)->toArray();
        }, $forms);
    }

    /**
     * One SQL statement for the admin form table. Translation badges and the
     * field count are relational list data; they must not be assembled with
     * one translation/field query per form in PHP.
     *
     * @param array<string, mixed> $criteria
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int}
     */
    private function listAdminProjection(array $criteria): array
    {
        $page = max(1, (int) ($criteria['page'] ?? 1));
        $perPage = min(1000, max(1, (int) ($criteria['per_page'] ?? $criteria['limit'] ?? 25)));
        $offset = ($page - 1) * $perPage;
        $where = [];
        $binds = [];

        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $where[] = <<<'SQL'
(
    f.form_key LIKE ?
    OR EXISTS (
        SELECT 1 FROM cms_form_translations ft_search
        WHERE ft_search.form_id = f.id
          AND ft_search.name LIKE ?
    )
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle);
        }

        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sortExpressions = [
            'form_key'    => ['f.form_key ASC, f.id ASC', 'p.form_key ASC, p.id ASC'],
            '-form_key'   => ['f.form_key DESC, f.id DESC', 'p.form_key DESC, p.id DESC'],
            'is_active'   => ['f.is_active ASC, f.id ASC', 'p.is_active ASC, p.id ASC'],
            '-is_active'  => ['f.is_active DESC, f.id DESC', 'p.is_active DESC, p.id DESC'],
            'created_at'  => ['f.created_at ASC, f.id ASC', 'p.created_at ASC, p.id ASC'],
            '-created_at' => ['f.created_at DESC, f.id DESC', 'p.created_at DESC, p.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sortExpressions[$sort] ?? $sortExpressions['-created_at'];
        $whereSql = $where === [] ? '1 = 1' : implode("\n      AND ", $where);

        $sql = <<<SQL
WITH filtered_forms AS (
    SELECT
        f.id,
        f.form_key,
        f.is_active,
        f.has_captcha,
        f.notify_email,
        f.autoreply_enabled,
        f.autoreply_email_field,
        f.created_at,
        f.updated_at,
        (SELECT COUNT(*) FROM cms_form_fields ff WHERE ff.form_id = f.id) AS fields_count,
        COUNT(*) OVER () AS total_items
    FROM cms_forms f
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT
    p.id,
    p.form_key,
    p.is_active,
    p.has_captcha,
    p.notify_email,
    p.autoreply_enabled,
    p.autoreply_email_field,
    p.fields_count,
    p.created_at,
    p.updated_at,
    GROUP_CONCAT(
        CONCAT(ft.language_id, ':', COALESCE(HEX(ft.name), ''))
        ORDER BY ft.language_id ASC, ft.id ASC SEPARATOR '|'
    ) AS translations_data,
    MAX(p.total_items) AS total_items
FROM filtered_forms p
LEFT JOIN cms_form_translations ft ON ft.form_id = p.id
GROUP BY p.id, p.form_key, p.is_active, p.has_captcha, p.notify_email,
         p.autoreply_enabled, p.autoreply_email_field, p.fields_count,
         p.created_at, p.updated_at
ORDER BY {$outerOrder}
SQL;

        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('SET SESSION group_concat_max_len = 1048576');
        }

        $query = $this->db->query($sql, $binds);
        if (! $query instanceof \CodeIgniter\Database\ResultInterface) {
            throw new \RuntimeException(lang('Forms.list_projection_failed'));
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $query->getResultArray();
        $total = $rows !== [] ? (int) ($rows[0]['total_items'] ?? 0) : 0;
        $data = array_map(static function (array $row): array {
            $translations = AdminListProjectionDecoder::translations($row['translations_data'] ?? null, ['name']);
            unset($row['translations_data'], $row['total_items']);
            $row['translations'] = $translations;
            $row['fields_count'] = (int) ($row['fields_count'] ?? 0);

            return $row;
        }, $rows);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
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

        $owners = array_map(
            static fn (array $row): array => [
                'owner_type' => (string) ($row['owner_type'] ?? ''),
                'owner_id'   => (int) ($row['owner_id'] ?? 0),
            ],
            $rows
        );
        $ownerTitles = $this->ownerUsageResolver->resolveTitles($owners, $locale);

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

    /**
     * @param array<string, mixed> $formData
     */
    private function buildFormResponse(array $formData, ?string $locale = null): FormResponseDTO
    {
        $trans = $this->translationModel->where('form_id', $formData['id'])->findAll();

        $formData['translations'] = ModelResultNormalizer::toArrayList($trans);
        $formData['fields']       = $this->fieldService->listWithTranslations((int) $formData['id']);
        $formData['usages']       = $this->getUsages((int) $formData['id'], $locale);

        return FormResponseDTO::fromArray($formData);
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
        $rows = [];
        foreach ($translations as $trans) {
            if (! is_array($trans)) {
                continue;
            }
            $languageId = (int) ($trans['language_id'] ?? 0);
            if ($languageId === 0) {
                continue;
            }

            $rows[] = [
                'language_id'     => $languageId,
                'name'            => (string) ($trans['name'] ?? ''),
                'description'     => isset($trans['description']) && $trans['description'] !== '' ? (string) $trans['description'] : null,
                'submit_label'    => (string) ($trans['submit_label'] ?? 'Enviar'),
                'success_message' => isset($trans['success_message']) && $trans['success_message'] !== '' ? (string) $trans['success_message'] : null,
                'error_message'   => isset($trans['error_message']) && $trans['error_message'] !== '' ? (string) $trans['error_message'] : null,
            ];
        }

        ($this->translationSynchronizer ?? throw new \LogicException(lang('Api.translationSynchronizerRequired')))->replace(
            $this->translationModel,
            'form_id',
            $formId,
            $rows,
            static fn (array $row): array => $row,
        );
    }
}
