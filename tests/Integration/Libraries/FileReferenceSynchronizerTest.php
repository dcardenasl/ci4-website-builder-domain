<?php

declare(strict_types=1);

namespace Tests\Integration\Libraries;

use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
use App\Services\Cms\FileUsageService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/** @internal */
final class FileReferenceSynchronizerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testFreshSchemaSynchronizesNestedReferencesAndCascadesBlockDeletion(): void
    {
        $db = Database::connect();
        $this->assertTrue($db->tableExists('cms_file_references'));
        $longConfigGroup = 'configuration_group_with_a_name_long_enough_to_exceed_the_role_column_limit';
        $longLabel = str_repeat('Etiqueta extensa ', 30);

        $languageId = $this->ensureSpanishLanguage($db);
        $db->table('cms_content_blocks')->insert([
            'block_key' => 'media_test_' . bin2hex(random_bytes(4)),
            'name' => 'Media test',
            'schema_definition' => json_encode([
                'fields' => [
                    'items' => [
                        'type' => 'repeater',
                        'item_fields' => [
                            'image' => ['type' => 'media_reference', 'accept' => 'image'],
                        ],
                    ],
                ],
                'config_fields' => [
                    'cover' => ['type' => 'media_reference', 'accept' => 'image'],
                    $longConfigGroup => [
                        'type' => 'group',
                        'fields' => [
                            'nested_cover' => [
                                'type' => 'media_reference',
                                'accept' => 'image',
                                'label' => $longLabel,
                            ],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            'supports_pages' => 1,
            'supports_entries' => 1,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 0,
        ]);
        $blockId = (int) $db->insertID();
        $db->table('cms_block_instances')->insert([
            'block_id' => $blockId,
            'owner_type' => 'page',
            'owner_id' => 99,
            'sort_order' => 0,
            'is_active' => 1,
            'block_config' => json_encode([
                'cover' => ['source_kind' => 'hub_file', 'file_id' => 900, 'url' => '/files/900/view'],
                $longConfigGroup => [
                    'nested_cover' => ['source_kind' => 'hub_file', 'file_id' => 902, 'url' => '/files/902/view'],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);
        $instanceId = (int) $db->insertID();
        $db->table('cms_block_instance_translations')->insert([
            'instance_id' => $instanceId,
            'language_id' => $languageId,
            'block_data' => json_encode([
                'items' => [
                    ['image' => ['source_kind' => 'hub_file', 'file_id' => 901, 'url' => '/files/901/view']],
                ],
            ], JSON_THROW_ON_ERROR),
            'is_published' => 1,
        ]);

        $resolver = new class () extends FileUrlResolver {
            public function __construct()
            {
            }
        };
        $synchronizer = new FileReferenceSynchronizer($resolver, $db);

        $synchronizer->syncBlockInstance($instanceId);
        $synchronizer->syncBlockInstance($instanceId);

        $rows = $db->table('cms_file_references')
            ->select('hub_file_id, resource_type, resource_id, block_instance_id, role, label')
            ->where('resource_type', 'block_instance')
            ->where('resource_id', $instanceId)
            ->orderBy('hub_file_id', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(3, $rows);
        $this->assertSame(['900', '901', '902'], array_column($rows, 'hub_file_id'));
        $this->assertSame('config.cover', $rows[0]['role']);
        $this->assertSame('items[0].image.es', $rows[1]['role']);
        $this->assertSame(50, strlen((string) $rows[2]['role']));
        $this->assertStringContainsString('~', (string) $rows[2]['role']);
        $this->assertSame(255, mb_strlen((string) $rows[2]['label']));

        foreach ($rows as $row) {
            $this->assertSame('block_instance', $row['resource_type']);
            $this->assertSame((string) $instanceId, $row['resource_id']);
            $this->assertSame((string) $instanceId, $row['block_instance_id']);
        }

        $usages = (new FileUsageService($db))->getUsagesByHubFileId(901);
        $this->assertCount(1, $usages);
        $this->assertSame('block_instances', $usages[0]['resource']);
        $this->assertStringStartsWith('media_test_', $usages[0]['context']['block_key']);

        $db->table('cms_block_instances')->where('id', $instanceId)->delete();
        $this->assertSame(0, $db->table('cms_file_references')->where('resource_id', $instanceId)->countAllResults());
    }

    public function testSettingReferencesAreCanonicalAndSelfCleaning(): void
    {
        $db = Database::connect();
        $languageId = $this->ensureSpanishLanguage($db);
        $db->table('cms_settings')->insert([
            'setting_key' => 'test_logo_' . bin2hex(random_bytes(4)),
            'setting_value' => '77',
            'setting_type' => 'file_id',
            'setting_group' => 'identity',
            'is_translatable' => 1,
            'is_public' => 1,
            'is_active' => 1,
            'is_required' => 0,
            'is_readonly' => 0,
            'sort_order' => 0,
        ]);
        $settingId = (int) $db->insertID();
        $db->table('cms_setting_translations')->insert([
            'setting_id' => $settingId,
            'language_id' => $languageId,
            'setting_value' => '78',
        ]);

        $resolver = new class () extends FileUrlResolver {
            public function __construct()
            {
            }
        };
        $synchronizer = new FileReferenceSynchronizer($resolver, $db);
        $synchronizer->syncSetting($settingId);

        $this->assertSame('settings', (new FileUsageService($db))->getUsagesByHubFileId(77)[0]['resource']);
        $translatedUsage = (new FileUsageService($db))->getUsagesByHubFileId(78);
        $this->assertSame('setting_value.es', $translatedUsage[0]['role']);

        $db->table('cms_settings')->where('id', $settingId)->update(['setting_value' => null]);
        $db->table('cms_setting_translations')->where('setting_id', $settingId)->update(['setting_value' => null]);
        $synchronizer->syncSetting($settingId);

        $this->assertSame([], (new FileUsageService($db))->getUsagesByHubFileId(77));
        $this->assertSame([], (new FileUsageService($db))->getUsagesByHubFileId(78));
    }

    /**
     * @param \CodeIgniter\Database\BaseConnection<mixed, mixed> $db
     */
    private function ensureSpanishLanguage(\CodeIgniter\Database\BaseConnection $db): int
    {
        $existing = $db->table('cms_languages')->select('id')->where('code', 'es')->get()->getRowArray();
        if (is_array($existing)) {
            return (int) $existing['id'];
        }

        $db->table('cms_languages')->insert([
            'code' => 'es',
            'name' => 'Spanish',
            'native_name' => 'Español',
            'is_default' => 1,
            'is_active' => 1,
        ]);

        return (int) $db->insertID();
    }
}
