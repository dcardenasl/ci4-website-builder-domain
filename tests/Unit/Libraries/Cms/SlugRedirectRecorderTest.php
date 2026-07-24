<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\SlugRedirectRecorder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @internal
 */
final class SlugRedirectRecorderTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testRecordInsertsRedirectWhenSlugChanges(): void
    {
        $db = Database::connect();
        $db->disableForeignKeyChecks();
        $db->query("DELETE FROM `cms_slug_redirects`");
        $db->query("DELETE FROM `cms_languages`");
        $db->enableForeignKeyChecks();

        // Seed language
        $db->table('cms_languages')->insert([
            'id'          => 1,
            'code'        => 'es',
            'name'        => 'Spanish',
            'native_name' => 'Español',
            'is_default'  => 1,
            'is_active'   => 1,
        ]);

        $recorder = new SlugRedirectRecorder($db);
        $recorder->record('page', 10, 1, 'slug-viejo', 'slug-nuevo', 'seccion/slug-viejo');

        $row = $db->table('cms_slug_redirects')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame('page', $row['entity_type']);
        $this->assertSame(10, (int)$row['entity_id']);
        $this->assertSame(1, (int)$row['language_id']);
        $this->assertSame('slug-viejo', $row['old_slug']);
        $this->assertSame('seccion/slug-viejo', $row['old_full_path']);
    }

    public function testRecordDoesNotInsertDuplicate(): void
    {
        $db = Database::connect();
        $db->disableForeignKeyChecks();
        $db->query("DELETE FROM `cms_slug_redirects`");
        $db->query("DELETE FROM `cms_languages`");
        $db->enableForeignKeyChecks();

        $db->table('cms_languages')->insert([
            'id'          => 1,
            'code'        => 'es',
            'name'        => 'Spanish',
            'native_name' => 'Español',
            'is_default'  => 1,
            'is_active'   => 1,
        ]);

        $recorder = new SlugRedirectRecorder($db);

        // 1st record
        $recorder->record('page', 10, 1, 'slug-viejo', 'slug-nuevo', 'slug-viejo');
        // Duplicate record call
        $recorder->record('page', 10, 1, 'slug-viejo', 'slug-nuevo', 'slug-viejo');

        $count = $db->table('cms_slug_redirects')->countAllResults();
        $this->assertSame(1, $count);
    }
}
