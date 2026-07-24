<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\JsonCastNormalizer;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class JsonCastNormalizerTest extends CIUnitTestCase
{
    public function testPassesThroughAnAlreadyDecodedArray(): void
    {
        $this->assertSame(['icon' => '📄'], JsonCastNormalizer::toArray(['icon' => '📄']));
    }

    public function testDecodesARawJsonString(): void
    {
        $this->assertSame(['icon' => '📄'], JsonCastNormalizer::toArray('{"icon":"📄"}'));
    }

    public function testDecodesAStdClassRecursivelyAtEveryNestingLevel(): void
    {
        $nested = json_decode('{"fields":{"heading":{"type":"string","required":true}}}');

        $result = JsonCastNormalizer::toArray($nested);

        $this->assertIsArray($result['fields']);
        $this->assertIsArray($result['fields']['heading']);
        $this->assertSame('string', $result['fields']['heading']['type']);
        $this->assertTrue($result['fields']['heading']['required']);
    }

    public function testReturnsEmptyArrayForNull(): void
    {
        $this->assertSame([], JsonCastNormalizer::toArray(null));
    }

    public function testReturnsEmptyArrayForAnEmptyString(): void
    {
        $this->assertSame([], JsonCastNormalizer::toArray(''));
    }

    public function testReturnsEmptyArrayForMalformedJson(): void
    {
        $this->assertSame([], JsonCastNormalizer::toArray('{not valid json'));
    }
}
