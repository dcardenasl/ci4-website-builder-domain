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
}
