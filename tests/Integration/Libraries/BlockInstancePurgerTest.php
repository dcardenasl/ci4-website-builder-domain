<?php

declare(strict_types=1);

namespace Tests\Integration\Libraries;

use App\Libraries\Cms\BlockInstancePurger;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Tests\Support\Fixtures\CmsFixtureFactory;

/** @internal */
final class BlockInstancePurgerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testPurgeRemovesOwnerBlocksTranslationsAndFileReferences(): void
    {
        $database = Database::connect();
        $fixtures = new CmsFixtureFactory($database, self::class);
        $page = $fixtures->page();
        $language = $fixtures->languages(1)[0];

        $database->table('cms_content_blocks')->insert([
            'block_key'         => 'purge_test',
            'name'              => 'Purge test',
            'schema_definition' => '{}',
        ]);
        $blockTypeId = (int) $database->insertID();
        $root = $fixtures->block($blockTypeId, 'page', (int) $page['id']);
        $child = $fixtures->block($blockTypeId, 'page', (int) $page['id'], [
            'parent_instance_id' => $root['id'],
        ]);

        foreach ([$root['id'], $child['id']] as $instanceId) {
            $database->table('cms_block_instance_translations')->insert([
                'instance_id'  => $instanceId,
                'language_id'  => $language['id'],
                'block_data'   => '{}',
                'is_published' => 1,
            ]);
        }

        $database->table('cms_file_references')->insert([
            'hub_file_id'       => 7001,
            'resource_type'     => 'page',
            'resource_id'       => $page['id'],
            'block_instance_id' => $root['id'],
            'role'              => 'hero',
        ]);

        $purged = (new BlockInstancePurger($database))->purgeForOwner('page', (int) $page['id']);

        self::assertSame(2, $purged);
        self::assertSame(0, $database->table('cms_block_instances')->where('owner_type', 'page')->where('owner_id', $page['id'])->countAllResults());
        self::assertSame(0, $database->table('cms_block_instance_translations')->whereIn('instance_id', [$root['id'], $child['id']])->countAllResults());
        self::assertSame(0, $database->table('cms_file_references')->where('block_instance_id', $root['id'])->countAllResults());
    }

    public function testInvalidOwnerTypeDoesNotDeleteBlocks(): void
    {
        $database = Database::connect();
        $fixtures = new CmsFixtureFactory($database, self::class);
        $page = $fixtures->page();

        self::assertSame(0, (new BlockInstancePurger($database))->purgeForOwner('site', (int) $page['id']));
    }
}
