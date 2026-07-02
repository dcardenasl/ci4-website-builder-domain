<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\CmsPresetCatalog;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CmsPresetCatalogTest extends CIUnitTestCase
{
    public function testKnownCollectionTypesResolveDistinctPresets(): void
    {
        $blog = CmsPresetCatalog::resolveCollection('blog');
        $news = CmsPresetCatalog::resolveCollection('news');

        $this->assertSame('blog', $blog['type_key']);
        $this->assertSame('news', $news['type_key']);
        $this->assertNotSame($blog['block_template'], $news['block_template']);
        $this->assertNotSame($blog['wizard_config'], $news['wizard_config']);
    }

    public function testKnownPageTypesResolveDistinctPresets(): void
    {
        $home = CmsPresetCatalog::resolvePage('home');
        $contact = CmsPresetCatalog::resolvePage('contact');

        $this->assertSame('home', $home['type_key']);
        $this->assertSame('contact', $contact['type_key']);
        $this->assertNotSame($home['block_template'], $contact['block_template']);
        $this->assertNull($home['wizard_config']);
        $this->assertNull($contact['wizard_config']);
    }

    public function testUnknownTypesFallBackToSafeDefaults(): void
    {
        $collection = CmsPresetCatalog::resolveCollection('unknown');
        $page = CmsPresetCatalog::resolvePage('unknown');

        $this->assertSame('other', $collection['type_key']);
        $this->assertSame('generic', $page['type_key']);
        $this->assertNotEmpty($collection['block_template']['blocks']);
        $this->assertNotEmpty($page['block_template']['blocks']);
    }
}
