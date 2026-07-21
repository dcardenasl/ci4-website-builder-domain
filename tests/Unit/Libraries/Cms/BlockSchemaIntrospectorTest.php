<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\BlockSchemaIntrospector;
use App\Libraries\Cms\FieldPrimitiveRegistry;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BlockSchemaIntrospectorTest extends CIUnitTestCase
{
    public function testRegistryNormalizesNativePrimitiveAliases(): void
    {
        $registry = new FieldPrimitiveRegistry();

        $this->assertSame('text', $registry->normalize('string'));
        $this->assertSame('textarea', $registry->normalize('text'));
        $this->assertSame('richtext', $registry->normalize('rich_text'));
        $this->assertSame('media_reference', $registry->normalize('media_reference'));
        $this->assertSame('unsupported', $registry->normalize('file'));
    }

    public function testIntrospectorDerivesBlockCapabilitiesFromFields(): void
    {
        $introspector = new BlockSchemaIntrospector(new FieldPrimitiveRegistry());

        $result = $introspector->introspect([
            'fields' => [
                'body' => ['type' => 'rich_text', 'required' => true],
                'cover' => ['type' => 'media_reference', 'accept' => 'image'],
                'items' => ['type' => 'repeater'],
            ],
        ]);

        $this->assertTrue($result['contains_richtext']);
        $this->assertTrue($result['contains_image']);
        $this->assertTrue($result['contains_file']);
        $this->assertSame(['body'], $result['required_fields']);
        $this->assertSame(['body'], $result['translatable_fields']);
        $this->assertSame(['items'], $result['unsupported_fields']);
        $this->assertSame('media_reference', $result['fields']['cover']['primitive']);
    }

    /**
     * Regression for the 2026-07-21 DOM-122 bug: a `json`-cast Entity
     * property decodes to `stdClass` recursively at every nesting level, not
     * just the top one. introspect() must self-normalize via
     * JsonCastNormalizer so a caller handing over the raw Entity property
     * (instead of pre-decoding it) never silently gets an empty result.
     */
    public function testIntrospectorAcceptsAStdClassSchemaWithNestedStdClassFields(): void
    {
        $introspector = new BlockSchemaIntrospector(new FieldPrimitiveRegistry());

        $schema = json_decode('{"fields":{"heading":{"type":"rich_text","required":true}},"config_fields":{"style":{"type":"string"}}}');

        $result = $introspector->introspect($schema);

        $this->assertArrayHasKey('heading', $result['fields']);
        $this->assertTrue($result['contains_richtext']);
        $this->assertSame(['heading'], $result['required_fields']);
    }
}
