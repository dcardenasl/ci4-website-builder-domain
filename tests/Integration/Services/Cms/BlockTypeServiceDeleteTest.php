<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\ConflictException;

/**
 * @internal
 */
final class BlockTypeServiceDeleteTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testDestroyThrowsConflictWhenBlockTypeIsInUse(): void
    {
        $db = Database::connect();

        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->query('DELETE FROM `cms_block_instance_translations`');
        $db->query('DELETE FROM `cms_block_instances`');
        $db->query('DELETE FROM `cms_content_blocks`');
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        $db->table('cms_content_blocks')->insert([
            'id' => 40,
            'block_key' => 'seccion_destacados_inicio',
            'name' => 'Sección de destacados de inicio',
            'schema_definition' => json_encode(['fields' => []]),
            'supports_pages' => 1,
            'supports_entries' => 1,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 1,
        ]);

        $db->table('cms_block_instances')->insert([
            'id' => 109,
            'block_id' => 40,
            'owner_type' => 'page',
            'owner_id' => 7,
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $service = Services::blockTypeService(false);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage(lang('Cms.block_types.in_use', ['1']));

        $service->destroy(40, null);
    }

    public function testDestroySucceedsWhenBlockTypeIsUnused(): void
    {
        $db = Database::connect();

        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->query('DELETE FROM `cms_block_instance_translations`');
        $db->query('DELETE FROM `cms_block_instances`');
        $db->query('DELETE FROM `cms_content_blocks`');
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        $db->table('cms_content_blocks')->insert([
            'id' => 41,
            'block_key' => 'unused_block',
            'name' => 'Unused Block',
            'schema_definition' => json_encode(['fields' => []]),
            'supports_pages' => 1,
            'supports_entries' => 1,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 1,
        ]);

        $service = Services::blockTypeService(false);

        $this->assertTrue($service->destroy(41, null));
        $this->assertNull($db->table('cms_content_blocks')->getWhere(['id' => 41])->getRowArray());
    }
}
