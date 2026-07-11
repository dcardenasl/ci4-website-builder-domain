<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Entities\BlockTypeEntity;
use App\Libraries\Cms\BlockTemplateCatalog;
use App\Models\BlockTypeModel;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

/**
 * Parity tests for BlockTemplateCatalog. Until 2026-07-11 this catalog was a
 * hand-maintained array that drifted from the persisted `cms_content_blocks`
 * schema (finding H-010). It now projects the repository row directly, so
 * these tests assert the projection is faithful to whatever is persisted —
 * not a second, independent contract that can drift again.
 *
 * @internal
 */
final class BlockTemplateCatalogTest extends CIUnitTestCase
{
    /**
     * @param list<BlockTypeEntity> $rows
     */
    private function catalogFor(array $rows): BlockTemplateCatalog
    {
        // getModel()->findAll() — not the interface's findAll() — is the real
        // call `all()` makes. RepositoryInterface::findAll() defaults $limit
        // to 0, which this project's Config\Feature::$limitZeroAsAll = false
        // treats as "zero rows", so the mock has to mirror the actual seam.
        $model = $this->createMock(BlockTypeModel::class);
        $model->method('findAll')->willReturn($rows);

        $repository = $this->createMock(RepositoryInterface::class);
        $repository->method('getModel')->willReturn($model);

        return new BlockTemplateCatalog($repository);
    }

    private function row(array $attributes): BlockTypeEntity
    {
        return new BlockTypeEntity(array_merge([
            'id' => 1,
            'description' => null,
            'icon' => null,
            'supports_pages' => true,
            'supports_entries' => true,
            'is_container' => false,
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));
    }

    public function testDefaultSchemaIsReadVerbatimFromThePersistedRow(): void
    {
        $schema = [
            'fields' => ['whatever_field' => ['type' => 'string', 'label' => 'X', 'required' => false]],
            'config_fields' => [],
        ];
        $catalog = $this->catalogFor([$this->row([
            'block_key' => 'made_up_block',
            'name' => 'Made Up Block',
            'category' => 'general',
            'schema_definition' => json_encode($schema),
        ])]);

        $template = $catalog->findByKey('made_up_block');

        $this->assertIsArray($template);
        $this->assertSame($schema, $template['default_schema']);
    }

    public function testHeroSliderIsProjectedAsAContainerWithChildrenNotFlatSlideFields(): void
    {
        $schema = [
            'fields' => [],
            'config_fields' => [
                'autoplay' => ['type' => 'boolean', 'label' => 'Autoplay', 'required' => false, 'default' => true],
                'interval' => ['type' => 'number', 'label' => 'Intervalo (ms)', 'required' => false, 'default' => 6000],
                'caption_position' => ['type' => 'select', 'label' => 'Posición del texto', 'options' => ['below', 'overlay_top'], 'default' => 'below', 'required' => false],
            ],
            'allowed_children' => ['slide_banner'],
        ];
        $catalog = $this->catalogFor([$this->row([
            'block_key' => 'hero_slider',
            'name' => 'Carrusel Hero',
            'category' => 'marketing',
            'is_container' => true,
            'schema_definition' => json_encode($schema),
        ])]);

        $template = $catalog->findByKey('hero_slider');

        $this->assertIsArray($template);
        $this->assertSame([], $template['default_schema']['fields']);
        $this->assertSame(['slide_banner'], $template['default_schema']['allowed_children']);
        $this->assertSame(true, $template['default_schema']['config_fields']['autoplay']['default']);
        $this->assertSame(6000, $template['default_schema']['config_fields']['interval']['default']);
        $this->assertSame('container', $template['content_source']['type']);

        // No flat slide_1_*/slide_2_*/slide_3_* fields exist on this schema, so
        // the curated preview sample must not fabricate any — that stale
        // contract (H-010) is exactly what this test used to protect.
        $this->assertSame([], $template['preview_sample']);
    }

    public function testImageBlockUsesTheSingleFileFieldNotLegacyFileIdAndUrl(): void
    {
        $schema = [
            'fields' => [
                'image' => ['type' => 'file', 'label' => 'Imagen', 'required' => false, 'accept' => 'image'],
                'alt' => ['type' => 'string', 'label' => 'Texto Alternativo', 'required' => false],
                'caption' => ['type' => 'string', 'label' => 'Pie de Foto', 'required' => false],
            ],
            'config_fields' => [],
        ];
        $catalog = $this->catalogFor([$this->row([
            'block_key' => 'image',
            'name' => 'Imagen',
            'category' => 'media',
            'schema_definition' => json_encode($schema),
        ])]);

        $template = $catalog->findByKey('image');

        $this->assertIsArray($template);
        $this->assertArrayHasKey('image', $template['default_schema']['fields']);
        $this->assertArrayNotHasKey('file_id', $template['default_schema']['fields']);
        $this->assertArrayNotHasKey('url', $template['default_schema']['fields']);
        // "image" is a file field with no sensible text sample; only alt/caption are curated.
        $this->assertSame(['alt' => 'Imagen de ejemplo', 'caption' => 'Pie de foto de ejemplo'], $template['preview_sample']);
    }

    public function testInactiveBlockTypesAreExcluded(): void
    {
        $catalog = $this->catalogFor([
            $this->row(['block_key' => 'active_one', 'name' => 'Active', 'category' => 'general', 'schema_definition' => '{"fields":[],"config_fields":[]}', 'is_active' => true]),
            $this->row(['block_key' => 'retired_one', 'name' => 'Retired', 'category' => 'general', 'schema_definition' => '{"fields":[],"config_fields":[]}', 'is_active' => false]),
        ]);

        $keys = array_column($catalog->all(), 'key');

        $this->assertContains('active_one', $keys);
        $this->assertNotContains('retired_one', $keys);
    }

    public function testConfigSampleIsDerivedFromSchemaDefaultsNotACuratedDuplicate(): void
    {
        $schema = [
            'fields' => [],
            'config_fields' => [
                'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => 'my-class'],
                'no_default_field' => ['type' => 'string', 'label' => 'No default', 'required' => false],
            ],
        ];
        $catalog = $this->catalogFor([$this->row([
            'block_key' => 'anything',
            'name' => 'Anything',
            'category' => 'general',
            'schema_definition' => json_encode($schema),
        ])]);

        $template = $catalog->findByKey('anything');

        $this->assertIsArray($template);
        $this->assertSame(['css_class' => 'my-class'], $template['config_sample']);
    }

    public function testPreviewSampleSelfHealsWhenACuratedFieldNoLongerExistsInTheSchema(): void
    {
        // hero_banner has a curated cta_url sample, but this row's schema
        // dropped that field — the stale sample key must not leak through.
        $schema = [
            'fields' => [
                'heading' => ['type' => 'string', 'label' => 'Título', 'required' => true],
            ],
            'config_fields' => [],
        ];
        $catalog = $this->catalogFor([$this->row([
            'block_key' => 'hero_banner',
            'name' => 'Hero Banner',
            'category' => 'marketing',
            'schema_definition' => json_encode($schema),
        ])]);

        $template = $catalog->findByKey('hero_banner');

        $this->assertIsArray($template);
        $this->assertArrayHasKey('heading', $template['preview_sample']);
        $this->assertArrayNotHasKey('cta_url', $template['preview_sample']);
    }
}
