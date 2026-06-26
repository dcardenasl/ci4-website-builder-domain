<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\HtmlSanitizer;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class HtmlSanitizerTest extends CIUnitTestCase
{
    public function testCleanPreservesSupportedRichTextMarkup(): void
    {
        $html = '<p>Texto <strong>enriquecido</strong> y <em>seguro</em></p>';

        $this->assertSame($html, HtmlSanitizer::clean($html));
    }

    public function testCleanStripsUnsupportedMarkElementsWithoutThrowing(): void
    {
        $html = '<p><mark>Marcado</mark> y <strong>texto</strong></p>';

        $this->assertSame('<p>Marcado y <strong>texto</strong></p>', HtmlSanitizer::clean($html));
    }
}
