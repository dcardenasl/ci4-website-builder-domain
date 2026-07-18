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
}
