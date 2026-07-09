<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

final class IdempotentSeederSupportTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->query('DELETE FROM `cms_page_translations`');
        $this->db->query('DELETE FROM `cms_pages`');
        $this->db->query('DELETE FROM `cms_languages`');
        $this->db->enableForeignKeyChecks();
    }

    public function testUpsertRowUpdatesExistingLocalizedRecordsWithoutDuplicatingThem(): void
    {
        $this->db->table('cms_languages')->insert([
            'code'        => 'es',
            'name'        => 'Español',
            'is_active'   => 1,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $languageId = (int) $this->db->insertID();

        $this->db->table('cms_pages')->insert([
            'parent_id'         => null,
            'collection_id'     => null,
            'page_type'         => 'generic',
            'status'            => 'published',
            'published_at'      => date('Y-m-d H:i:s'),
            'scheduled_at'      => null,
            'sort_order'        => 1,
            'sitemap_priority'  => null,
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap'     => 1,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $pageId = (int) $this->db->insertID();

        $seeder = new class ($this->db) extends Seeder {
            use IdempotentSeederSupport;

            public function __construct(\CodeIgniter\Database\BaseConnection $db)
            {
                parent::__construct(new Database(), $db);
            }

            public function run(): void
            {
            }

            public function upsert(string $table, array $lookup, array $data): ?int
            {
                return $this->upsertRecord($table, $lookup, $data);
            }
        };

        $firstId = $seeder->upsert('cms_page_translations', [
            'page_id'     => $pageId,
            'language_id' => $languageId,
        ], [
            'slug'             => 'nosotros',
            'title'            => 'Quiénes Somos',
            'excerpt'          => 'Primera versión',
            'meta_title'       => 'Quiénes Somos | Sitio',
            'meta_description' => 'Descripción inicial',
        ]);

        $this->assertNotNull($firstId);
        $this->assertSame(1, $this->db->table('cms_page_translations')->countAllResults());

        $secondId = $seeder->upsert('cms_page_translations', [
            'page_id'     => $pageId,
            'language_id' => $languageId,
        ], [
            'slug'             => 'nosotros',
            'title'            => 'Quiénes Somos Actualizado',
            'excerpt'          => 'Segunda versión',
            'meta_title'       => 'Quiénes Somos | Sitio',
            'meta_description' => 'Descripción actualizada',
        ]);

        $this->assertSame($firstId, $secondId);
        $this->assertSame(1, $this->db->table('cms_page_translations')->countAllResults());

        $row = $this->db->table('cms_page_translations')
            ->where('page_id', $pageId)
            ->where('language_id', $languageId)
            ->get()
            ->getRowArray();

        $this->assertSame('Quiénes Somos Actualizado', $row['title']);
        $this->assertSame('Segunda versión', $row['excerpt']);
    }
}
