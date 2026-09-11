<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use App\Entities\BlockTypeEntity;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Support\JsonCastNormalizer;

/**
 * Projects the persisted block type catalog (`cms_content_blocks`, the same
 * table BlockTypeService/BlockInstanceService read and validate against) into
 * the shape the "start from template" designer UI (Admin block type
 * create/edit screens) consumes.
 *
 * Until 2026-07-11 this class hand-maintained a second, static schema per
 * block key that silently drifted from the seeded/persisted schema — e.g.
 * `hero_slider` here described a flat 3-slide layout while the persisted row
 * had already moved to a container + `slide_banner` children model, and
 * `image` described `file_id`/`url` fields the persisted row no longer has
 * (finding H-010, docs/audits/2026-07-10-auditoria-profunda-robustez.md).
 *
 * `default_schema` is now always read straight from the persisted row, so it
 * cannot diverge again. Only `preview_sample`/`config_sample` stay curated
 * here as presentation-only sugar, and only the sample keys that still match
 * a real field in the persisted schema survive (see previewSample()) — a
 * schema change can only shrink a stale sample, never render one that lies
 * about what the block actually accepts.
 */
final class BlockTemplateCatalog
{
    /**
     * @param RepositoryInterface<BlockTypeEntity> $blockTypeRepository
     */
    public function __construct(private readonly RepositoryInterface $blockTypeRepository)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        // RepositoryInterface::findAll() defaults $limit to 0, and this project
        // runs with Config\Feature::$limitZeroAsAll = false — an explicit 0
        // means "zero rows", not "no limit". Go through the underlying model,
        // whose own findAll() defaults $limit to null (unbounded).
        /** @var list<BlockTypeEntity> $allRows */
        $allRows = $this->blockTypeRepository->getModel()->findAll();
        $rows = array_values(array_filter(
            $allRows,
            static fn (BlockTypeEntity $row): bool => (bool) $row->is_active,
        ));

        usort(
            $rows,
            static fn (BlockTypeEntity $a, BlockTypeEntity $b): int => [$a->category, $a->sort_order, $a->block_key]
                <=> [$b->category, $b->sort_order, $b->block_key]
        );

        return array_map($this->project(...), $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByKey(string $key): ?array
    {
        foreach ($this->all() as $template) {
            if ($template['key'] === $key) {
                return $template;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function project(BlockTypeEntity $row): array
    {
        $schema = $this->normalizeSchema($row->schema_definition);
        $fields = (array) ($schema['fields'] ?? []);
        $configFields = (array) ($schema['config_fields'] ?? []);
        $isContainer = (bool) $row->is_container;

        return [
            'key' => $row->block_key,
            'name' => $row->name,
            'description' => (string) ($row->description ?? ''),
            'category' => $row->category,
            'icon' => $row->icon,
            'default_schema' => $schema,
            'preview_sample' => $this->previewSample($row->block_key, $fields),
            'config_sample' => $this->configSample($configFields),
            'content_source' => $this->inferContentSource($row->block_key, $isContainer),
        ];
    }

    /**
     * BlockTypeEntity casts schema_definition as `json` (stdClass), but some
     * callers (repository/mapper layers) already hand it over pre-decoded as
     * a raw JSON string or a plain array — JsonCastNormalizer handles all
     * three shapes the same defensive way BlockTypeResponseDTO::fromArray()
     * and BlockSchemaIntrospector::introspect() do.
     *
     * @return array<string, mixed>
     */
    private function normalizeSchema(mixed $raw): array
    {
        return JsonCastNormalizer::toArray($raw);
    }

    /**
     * @param array<string, mixed> $configFields
     * @return array<string, mixed>
     */
    private function configSample(array $configFields): array
    {
        $sample = [];
        foreach ($configFields as $key => $definition) {
            if (is_array($definition) && array_key_exists('default', $definition)) {
                $sample[$key] = $definition['default'];
            }
        }

        return $sample;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function previewSample(string $key, array $fields): array
    {
        $curated = BlockPreviewSampleCatalog::sample($key);

        // Only keep curated keys that still exist in the persisted schema —
        // this is what makes the sample self-healing instead of re-drifting.
        return array_intersect_key($curated, $fields);
    }

    /**
     * @return array{type: string, label: string, description: string}
     */
    private function inferContentSource(string $key, bool $isContainer): array
    {
        if ($isContainer) {
            return [
                'type' => 'container',
                'label' => 'Contenedor',
                'description' => 'Bloque con hijos o piezas componibles.',
            ];
        }

        if (in_array($key, ['collection_grid', 'collection_listing'], true)) {
            return [
                'type' => 'collection',
                'label' => 'Colección',
                'description' => 'Bloque ligado a una colección.',
            ];
        }

        if (str_contains($key, 'page')) {
            return [
                'type' => 'page',
                'label' => 'Página',
                'description' => 'Bloque ligado a una página.',
            ];
        }

        return [
            'type' => 'manual',
            'label' => 'Manual',
            'description' => 'Bloque libre con contenido y configuración manuales.',
        ];
    }
}
