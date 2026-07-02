<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\CollectionPresetResolver;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CollectionPresetResolverTest extends CIUnitTestCase
{
    public function testBlogAndNewsPresetsDiffer(): void
    {
        $blog = CollectionPresetResolver::resolve('blog');
        $news = CollectionPresetResolver::resolve('news');

        $this->assertSame('blog', $blog['wizard_config']['type']);
        $this->assertSame('news', $news['wizard_config']['type']);
        $this->assertNotSame($blog['block_template'], $news['block_template']);
        $this->assertNotSame($blog['wizard_config'], $news['wizard_config']);
    }

    public function testUnknownTypeFallsBackToGenericPreset(): void
    {
        $preset = CollectionPresetResolver::resolve('unknown');

        $this->assertSame('other', $preset['wizard_config']['type']);
        $this->assertCount(1, $preset['block_template']['blocks']);
    }
}
