<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\BlockTemplateCatalog;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BlockTemplateCatalogTest extends CIUnitTestCase
{
    public function testHeroSliderTemplateExposesLayoutPositions(): void
    {
        $template = null;
        foreach (BlockTemplateCatalog::all() as $row) {
            if (($row['key'] ?? '') === 'hero_slider') {
                $template = $row;
                break;
            }
        }

        $this->assertIsArray($template);
        $this->assertSame('Carrusel Hero', $template['name']);

        $configFields = $template['default_schema']['config_fields'] ?? [];
        $this->assertArrayHasKey('caption_position', $configFields);
        $this->assertSame('below', $configFields['caption_position']['default']);
        $this->assertArrayHasKey('controls_position', $configFields);
        $this->assertSame('below', $configFields['controls_position']['default']);
        $this->assertArrayHasKey('overlay_opacity', $configFields);
        $this->assertSame('0', $configFields['overlay_opacity']['default']);

        $sample = $template['preview_sample'] ?? [];
        $this->assertStringStartsWith('data:image/svg+xml;charset=UTF-8,', (string) ($sample['slide_1_image_url'] ?? ''));
        $this->assertStringStartsWith('data:image/svg+xml;charset=UTF-8,', (string) ($sample['slide_2_image_url'] ?? ''));
        $this->assertStringStartsWith('data:image/svg+xml;charset=UTF-8,', (string) ($sample['slide_3_image_url'] ?? ''));
    }

    public function testPageHeaderBreadcrumbUrlCanBeRelative(): void
    {
        $template = null;
        foreach (BlockTemplateCatalog::all() as $row) {
            if (($row['key'] ?? '') === 'page_header') {
                $template = $row;
                break;
            }
        }

        $this->assertIsArray($template);

        $schema = $template['default_schema']['fields'] ?? [];
        $this->assertSame('string', $schema['breadcrumb_url']['type'] ?? null);
        $this->assertSame('/', $template['preview_sample']['breadcrumb_url'] ?? null);
    }

    public function testContactInfoAndMapEmbedAreSeparateTemplates(): void
    {
        $contactInfo = BlockTemplateCatalog::findByKey('contact_info');
        $mapEmbed    = BlockTemplateCatalog::findByKey('map_embed');

        $this->assertIsArray($contactInfo);
        $this->assertIsArray($mapEmbed);

        $contactFields = $contactInfo['default_schema']['fields'] ?? [];
        $contactConfig = $contactInfo['default_schema']['config_fields'] ?? [];
        $mapConfig     = $mapEmbed['default_schema']['config_fields'] ?? [];

        $this->assertArrayHasKey('email', $contactFields);
        $this->assertArrayHasKey('layout', $contactConfig);
        $this->assertArrayNotHasKey('map_embed_url', $contactConfig);
        $this->assertArrayHasKey('embed_url', $mapConfig);
    }

    public function testGenericCardAndMetricTemplatesDoNotExposeLegacyFields(): void
    {
        $slideCard  = BlockTemplateCatalog::findByKey('slide_card');
        $metricItem = BlockTemplateCatalog::findByKey('metric_item');

        $this->assertIsArray($slideCard);
        $this->assertIsArray($metricItem);

        $slideFields  = $slideCard['default_schema']['fields'] ?? [];
        $metricFields = $metricItem['default_schema']['fields'] ?? [];

        $this->assertArrayHasKey('body', $slideFields);
        $this->assertArrayHasKey('meta_title', $slideFields);
        $this->assertArrayNotHasKey('quote', $slideFields);
        $this->assertArrayNotHasKey('author', $slideFields);
        $this->assertArrayHasKey('prefix', $metricFields);
        $this->assertArrayHasKey('suffix', $metricFields);
        $this->assertArrayHasKey('source_label', $metricFields);
    }
}
