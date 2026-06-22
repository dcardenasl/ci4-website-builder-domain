<?php

declare(strict_types=1);

namespace Tests\Integration\Libraries;

use App\Libraries\Cms\BlockInstanceSerializer;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @internal
 */
final class BlockInstanceSerializerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private BlockInstanceSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = new BlockInstanceSerializer();
        $this->seedDatabase();
    }

    private function seedDatabase(): void
    {
        $db = Database::connect();

        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->table('cms_file_translations')->truncate();
        $db->table('cms_block_instance_translations')->truncate();
        $db->table('cms_block_instances')->truncate();
        $db->table('cms_content_blocks')->truncate();
        $db->table('cms_languages')->truncate();
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        // Languages
        $db->table('cms_languages')->insert([
            'id'          => 1,
            'code'        => 'en',
            'name'        => 'English',
            'native_name' => 'English',
            'is_default'  => 1,
            'is_active'   => 1,
        ]);
        $db->table('cms_languages')->insert([
            'id'          => 2,
            'code'        => 'es',
            'name'        => 'Spanish',
            'native_name' => 'Español',
            'is_default'  => 0,
            'is_active'   => 1,
        ]);

        // Block types
        $db->table('cms_content_blocks')->insert([
            'id'                => 1,
            'block_key'         => 'rich_text',
            'name'              => 'Rich Text',
            'schema_definition' => '{}',
            'supports_pages'    => 1,
            'supports_entries'  => 1,
            'is_container'      => 0,
            'is_active'         => 1,
            'sort_order'        => 10,
        ]);
        $db->table('cms_content_blocks')->insert([
            'id'                => 2,
            'block_key'         => 'image',
            'name'              => 'Image',
            'schema_definition' => json_encode(['fields' => ['image' => ['type' => 'file']]]),
            'supports_pages'    => 1,
            'supports_entries'  => 1,
            'is_container'      => 0,
            'is_active'         => 1,
            'sort_order'        => 20,
        ]);
        $db->table('cms_content_blocks')->insert([
            'id'                => 3,
            'block_key'         => 'hero_slider',
            'name'              => 'Hero Slider',
            'schema_definition' => '{}',
            'supports_pages'    => 1,
            'supports_entries'  => 0,
            'is_container'      => 1,
            'is_active'         => 1,
            'sort_order'        => 5,
        ]);

        // Block instances for page 10
        $db->table('cms_block_instances')->insert([
            'id'           => 100,
            'block_id'     => 1, // rich_text
            'owner_type'   => 'page',
            'owner_id'     => 10,
            'sort_order'   => 1,
            'is_active'    => 1,
            'block_config' => json_encode(['alignment' => 'left']),
        ]);
        $db->table('cms_block_instances')->insert([
            'id'           => 101,
            'block_id'     => 2, // image
            'owner_type'   => 'page',
            'owner_id'     => 10,
            'sort_order'   => 2,
            'is_active'    => 1,
            'block_config' => json_encode(['aspect_ratio' => '16:9']),
        ]);
        $db->table('cms_block_instances')->insert([
            'id'           => 102,
            'block_id'     => 3, // hero_slider
            'owner_type'   => 'page',
            'owner_id'     => 10,
            'sort_order'   => 3,
            'is_active'    => 1,
            'block_config' => json_encode([
                'caption_position'  => 'below',
                'controls_position' => 'below',
                'overlay_opacity'   => '0',
            ]),
        ]);

        // Block instance translations
        // 100 (rich_text) in English
        $db->table('cms_block_instance_translations')->insert([
            'instance_id'  => 100,
            'language_id'  => 1,
            'block_data'   => json_encode(['content' => 'Hello World']),
            'is_published' => 1,
        ]);
        // 100 (rich_text) in Spanish
        $db->table('cms_block_instance_translations')->insert([
            'instance_id'  => 100,
            'language_id'  => 2,
            'block_data'   => json_encode(['content' => 'Hola Mundo']),
            'is_published' => 1,
        ]);

        // 101 (image) in English only (test fallback) — uses canonical {field}_file_id convention
        $db->table('cms_block_instance_translations')->insert([
            'instance_id'  => 101,
            'language_id'  => 1,
            'block_data'   => json_encode(['image_file_id' => 500]),
            'is_published' => 1,
        ]);

        // File translations for file 500
        $db->table('cms_file_translations')->insert([
            'file_id'     => 500,
            'language_id' => 1,
            'alt_text'    => 'English Alt Text',
            'caption'     => 'English Caption',
            'title'       => 'Title',
            'credit'      => 'Credit',
            'description' => 'Desc',
        ]);
        $db->table('cms_file_translations')->insert([
            'file_id'     => 500,
            'language_id' => 2,
            'alt_text'    => 'Texto Alt Español',
            'caption'     => 'Subtítulo Español',
            'title'       => 'Título',
            'credit'      => 'Crédito',
            'description' => 'Descripción',
        ]);
    }

    public function testForContentResolvesLanguageCorrectly(): void
    {
        $blocks = $this->serializer->forContent('page', 10, 'es');

        $this->assertCount(3, $blocks);

        // First block: rich_text in Spanish
        $this->assertEquals(100, $blocks[0]['id']);
        $this->assertEquals('rich_text', $blocks[0]['block_key']);
        $this->assertEquals('left', $blocks[0]['block_config']['alignment']);
        $this->assertEquals('Hola Mundo', $blocks[0]['block_data']['content']);
        $this->assertFalse($blocks[0]['is_fallback']);

        // Second block: image (English fallback used, as Spanish translation for instance is missing)
        $this->assertEquals(101, $blocks[1]['id']);
        $this->assertEquals('image', $blocks[1]['block_key']);
        $this->assertEquals('16:9', $blocks[1]['block_config']['aspect_ratio']);
        $this->assertEquals(500, $blocks[1]['block_data']['image_file_id']);
        $this->assertTrue($blocks[1]['is_fallback']);

        // Alt text resolved to Spanish because file 500 has a Spanish translation
        $this->assertEquals('Texto Alt Español', $blocks[1]['block_data']['image_alt_text']);
        $this->assertEquals('Subtítulo Español', $blocks[1]['block_data']['image_caption']);

        // Third block: hero slider keeps the presentation config as stored
        $this->assertEquals(102, $blocks[2]['id']);
        $this->assertEquals('hero_slider', $blocks[2]['block_key']);
        $this->assertSame('below', $blocks[2]['block_config']['caption_position']);
        $this->assertSame('below', $blocks[2]['block_config']['controls_position']);
    }

    public function testForContentRetrievesDefaultWhenNoSpanishFileTranslation(): void
    {
        $blocks = $this->serializer->forContent('page', 10, 'en');

        $this->assertCount(3, $blocks);
        $this->assertEquals('Hello World', $blocks[0]['block_data']['content']);
        $this->assertEquals('English Alt Text', $blocks[1]['block_data']['image_alt_text']);
    }
}
