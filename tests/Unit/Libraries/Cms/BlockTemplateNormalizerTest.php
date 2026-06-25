<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\BlockTemplateNormalizer;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BlockTemplateNormalizerTest extends CIUnitTestCase
{
    public function testNormalizeCanonicalizesFlagsOrdersAndDefaults(): void
    {
        $template = BlockTemplateNormalizer::normalize([
            'version' => '1.0',
            'blocks' => [
                [
                    'block_key' => 'rich_text',
                    'label' => '  Intro  ',
                    'help_text' => '  Guide  ',
                    'sort_order' => '9',
                    'required' => 'false',
                    'locked' => '1',
                    'block_config_defaults' => [
                        'zeta' => 'last',
                        'alpha' => 'first',
                    ],
                ],
                [
                    'block_key' => 'image',
                    'required' => 1,
                    'locked' => 0,
                    'block_config_defaults' => [],
                ],
            ],
        ]);

        $this->assertIsArray($template);
        $this->assertSame('1.0', $template['version']);
        $this->assertCount(2, $template['blocks']);

        $first = $template['blocks'][0];
        $this->assertSame('rich_text', $first['block_key']);
        $this->assertSame(1, $first['sort_order']);
        $this->assertFalse($first['required']);
        $this->assertTrue($first['locked']);
        $this->assertSame('Intro', $first['label']);
        $this->assertSame('Guide', $first['help_text']);
        $this->assertSame(['alpha' => 'first', 'zeta' => 'last'], $first['block_config_defaults']);

        $second = $template['blocks'][1];
        $this->assertSame('image', $second['block_key']);
        $this->assertSame(2, $second['sort_order']);
        $this->assertTrue($second['required']);
        $this->assertFalse($second['locked']);
        $this->assertIsObject($second['block_config_defaults']);
        $this->assertSame('{}', json_encode($second['block_config_defaults'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
