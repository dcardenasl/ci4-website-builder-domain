<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\PagePresetResolver;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PagePresetResolverTest extends CIUnitTestCase
{
    public function testResolverReturnsPageCatalogPreset(): void
    {
        $preset = PagePresetResolver::resolve('events');

        $this->assertSame('events', $preset['type_key']);
        $this->assertNull($preset['wizard_config']);
        $this->assertSame('events_grid', $preset['block_template']['blocks'][1]['block_key'] ?? null);
    }

    public function testUnknownTypeFallsBackToGenericPagePreset(): void
    {
        $preset = PagePresetResolver::resolve('unknown');

        $this->assertSame('generic', $preset['type_key']);
        $this->assertNull($preset['wizard_config']);
    }
}
