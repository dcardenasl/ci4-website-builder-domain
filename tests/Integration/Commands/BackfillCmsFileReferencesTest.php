<?php

declare(strict_types=1);

namespace Tests\Integration\Commands;

use App\Commands\BackfillCmsFileReferences;
use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @internal
 */
final class BackfillCmsFileReferencesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testBackfillNormalizesNestedHeroSlideImageUrl(): void
    {
        $db = Database::connect();
        $resolver = new class () extends FileUrlResolver {
            public function __construct()
            {
            }

            public function resolveFileIdFromValue(int|string|null $fileId, ?string $url = null): ?int
            {
                return is_numeric($fileId) ? (int) $fileId : null;
            }

            public function resolveUrlValue(int|string|null $fileId, ?string $currentUrl = null, string $context = 'public'): ?string
            {
                return is_numeric($fileId) && (int) $fileId === 500
                    ? 'http://localhost:8180/uploads/posts/500.png'
                    : $currentUrl;
            }
        };
        $command = new BackfillCmsFileReferences(service('logger'), service('commands'));
        $command->setResolver($resolver);
        $command->setSynchronizer(new FileReferenceSynchronizer($db, $resolver));

        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->query("DELETE FROM `cms_block_instance_translations`");
        $db->query("DELETE FROM `cms_block_instances`");
        $db->query("DELETE FROM `cms_content_blocks`");
        $db->query("DELETE FROM `cms_languages`");
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        $db->table('cms_languages')->insert([
            'id'          => 1,
            'code'        => 'es',
            'name'        => 'Spanish',
            'native_name' => 'Español',
            'is_default'  => 1,
            'is_active'   => 1,
        ]);

        $db->table('cms_content_blocks')->insert([
            'id'                => 10,
            'block_key'         => 'hero_slider',
            'name'              => 'Hero Slider',
            'schema_definition' => json_encode([
                'fields' => [],
            ]),
            'supports_pages'    => 1,
            'supports_entries'  => 0,
            'is_container'      => 1,
            'is_active'         => 1,
            'sort_order'        => 1,
        ]);
        $db->table('cms_content_blocks')->insert([
            'id'                => 11,
            'block_key'         => 'slide_banner',
            'name'              => 'Slide Banner',
            'schema_definition' => json_encode([
                'fields' => [
                    'image'     => ['type' => 'file', 'label' => 'Image'],
                    'heading'   => ['type' => 'string', 'label' => 'Heading'],
                    'subtitle'  => ['type' => 'string', 'label' => 'Subtitle'],
                    'cta_label' => ['type' => 'string', 'label' => 'CTA'],
                    'cta_url'   => ['type' => 'url', 'label' => 'URL'],
                ],
            ]),
            'supports_pages'    => 1,
            'supports_entries'  => 0,
            'is_container'      => 0,
            'is_active'         => 1,
            'sort_order'        => 2,
        ]);
        $db->table('cms_content_blocks')->insert([
            'id'                => 12,
            'block_key'         => 'image',
            'name'              => 'Image',
            'schema_definition' => json_encode([
                'fields' => [
                    'alt' => ['type' => 'string', 'label' => 'Alt'],
                    'caption' => ['type' => 'string', 'label' => 'Caption'],
                ],
                'config_fields' => [
                    'image' => ['type' => 'media_reference', 'label' => 'Image', 'accept' => 'image'],
                ],
            ]),
            'supports_pages'    => 1,
            'supports_entries'  => 0,
            'is_container'      => 0,
            'is_active'         => 1,
            'sort_order'        => 3,
        ]);

        $db->table('cms_block_instances')->insert([
            'id'                 => 100,
            'block_id'           => 10,
            'owner_type'         => 'page',
            'owner_id'           => 99,
            'parent_instance_id' => null,
            'sort_order'         => 1,
            'is_active'          => 1,
            'block_config'       => json_encode([]),
        ]);
        $db->table('cms_block_instances')->insert([
            'id'                 => 102,
            'block_id'           => 12,
            'owner_type'         => 'page',
            'owner_id'           => 99,
            'parent_instance_id' => null,
            'sort_order'         => 3,
            'is_active'          => 1,
            'block_config'       => json_encode([
                'image' => [
                    'source_kind' => 'external_url',
                    'file_id' => 500,
                    'url' => 'http://localhost:8182/files/500/view',
                ],
            ]),
        ]);
        $db->table('cms_block_instances')->insert([
            'id'                 => 101,
            'block_id'           => 11,
            'owner_type'         => 'page',
            'owner_id'           => 99,
            'parent_instance_id' => 100,
            'sort_order'         => 1,
            'is_active'          => 1,
            'block_config'       => json_encode([]),
        ]);

        $db->table('cms_block_instance_translations')->insert([
            'instance_id'  => 101,
            'language_id'  => 1,
            'block_data'   => json_encode([
                'image_file_id' => 500,
                'image_url'     => '',
                'heading'       => 'Hero child',
                'subtitle'      => 'Nested slide',
                'cta_label'     => 'More',
                'cta_url'       => '/more',
            ]),
            'is_published' => 1,
        ]);
        $db->table('cms_block_instance_translations')->insert([
            'instance_id'  => 102,
            'language_id'  => 1,
            'block_data'   => json_encode([
                'alt' => 'Backfill alt',
                'caption' => 'Backfill caption',
            ]),
            'is_published' => 1,
        ]);

        $command->run(['--dry-run']);

        $before = $db->table('cms_block_instance_translations')
            ->where('instance_id', 101)
            ->get()
            ->getRowArray();

        $this->assertSame('', json_decode((string) $before['block_data'], true)['image_url']);

        $command->run([]);

        $after = $db->table('cms_block_instance_translations')
            ->where('instance_id', 101)
            ->get()
            ->getRowArray();
        $afterData = json_decode((string) $after['block_data'], true);

        $this->assertSame(500, $afterData['image_file_id']);
        $this->assertSame('http://localhost:8180/uploads/posts/500.png', $afterData['image_url']);

        $backfilledConfig = $db->table('cms_block_instances')
            ->where('id', 102)
            ->get()
            ->getRowArray();
        $backfilledConfigData = json_decode((string) $backfilledConfig['block_config'], true);

        $this->assertSame('external_url', $backfilledConfigData['image']['source_kind']);
        $this->assertNull($backfilledConfigData['image']['file_id']);
    }
}
