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
        $db->table('cms_languages')->truncate();
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        // 1. Languages
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

        // 2. File translations
        $db->table('cms_file_translations')->insert([
            'file_id'     => 100,
            'language_id' => 1,
            'alt_text'    => 'Default Alt Text',
            'caption'     => 'Default Caption',
        ]);

        $db->table('cms_file_translations')->insert([
            'file_id'     => 100,
            'language_id' => 2,
            'alt_text'    => 'Texto Alt en Español',
            'caption'     => 'Leyenda en Español',
        ]);
    }

    public function testEnrichImageBlockWithTargetLanguage(): void
    {
        $block = [
            'type'    => 'image',
            'file_id' => 100,
        ];

        $result = $this->serializer->enrichImageBlock($block, 'es');

        $this->assertSame('Texto Alt en Español', $result['alt_text']);
        $this->assertSame('Leyenda en Español', $result['caption']);
        $this->assertFalse($result['is_fallback']);
    }

    public function testEnrichImageBlockWithFallbackLanguage(): void
    {
        $block = [
            'type'    => 'image',
            'file_id' => 100,
        ];

        $result = $this->serializer->enrichImageBlock($block, 'fr'); // French, missing

        $this->assertSame('Default Alt Text', $result['alt_text']);
        $this->assertSame('Default Caption', $result['caption']);
        $this->assertTrue($result['is_fallback']);
    }

    public function testEnrichImageBlockWithoutFileIdReturnsUnchanged(): void
    {
        $block = [
            'type' => 'rich_text',
            'html' => '<p>Hello</p>',
        ];

        $result = $this->serializer->enrichImageBlock($block, 'es');

        $this->assertSame($block, $result);
    }
}
