<?php

declare(strict_types=1);

namespace Tests\Integration\Commands;

use App\Commands\RepairSlugs;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\Commands;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Psr\Log\LoggerInterface;
use Tests\Support\Fixtures\CmsFixtureFactory;

/** @internal */
final class RepairSlugsCommandTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    /** @var list<string>|null */
    private ?array $previousArgv = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousArgv = $_SERVER['argv'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->previousArgv !== null) {
            $_SERVER['argv'] = $this->previousArgv;
        } else {
            unset($_SERVER['argv']);
        }

        CLI::init();
        parent::tearDown();
    }

    public function testDryRunLeavesGenericSlugsUntouchedAndConfirmRepairsThem(): void
    {
        $fixtures  = new CmsFixtureFactory($this->db, self::class);
        $languages = $fixtures->languages(1);
        $languageId = $languages[0]['id'];
        $collection = $fixtures->collection([
            [
                'language_id' => $languageId,
                'slug'        => 'collection-old',
                'name'        => 'Noticias Destacadas',
            ],
        ]);
        $page = $fixtures->page([
            [
                'language_id' => $languageId,
                'slug'        => 'page-old',
                'title'       => 'Página de Inicio',
            ],
        ]);
        $entry = $fixtures->entry((int) $collection['id'], [
            [
                'language_id' => $languageId,
                'slug'        => 'entry-old',
                'title'       => 'Entrada Destacada',
            ],
        ]);

        $this->db->table('cms_categories')->insert([
            'collection_id' => $collection['id'],
            'sort_order'    => 0,
            'is_active'     => 1,
        ]);
        $categoryId = (int) $this->db->insertID();
        $this->db->table('cms_category_translations')->insert([
            'category_id' => $categoryId,
            'language_id' => $languageId,
            'slug'        => 'category-old',
            'name'        => 'Categoría Destacada',
        ]);

        $this->db->table('cms_tags')->insert(['is_active' => 1]);
        $tagId = (int) $this->db->insertID();
        $this->db->table('cms_tag_translations')->insert([
            'tag_id'      => $tagId,
            'language_id' => $languageId,
            'slug'        => 'tag-old',
            'name'        => 'Etiqueta Destacada',
        ]);

        $this->runCommand([]);

        self::assertSame('page-old', $this->slug('cms_page_translations', 'page_id', (int) $page['id']));
        self::assertSame('collection-old', $this->slug('cms_collection_translations', 'collection_id', (int) $collection['id']));

        $this->runCommand(['--confirm']);

        self::assertSame('pagina-de-inicio', $this->slug('cms_page_translations', 'page_id', (int) $page['id']));
        self::assertSame('noticias-destacadas', $this->slug('cms_collection_translations', 'collection_id', (int) $collection['id']));
        self::assertSame('entrada-destacada', $this->slug('cms_entry_translations', 'entry_id', (int) $entry['id']));
        self::assertSame('categoria-destacada', $this->slug('cms_category_translations', 'category_id', $categoryId));
        self::assertSame('etiqueta-destacada', $this->slug('cms_tag_translations', 'tag_id', $tagId));
    }

    /** @param list<string> $arguments */
    private function runCommand(array $arguments): void
    {
        $_SERVER['argv'] = array_merge(['spark', 'cms:repair-slugs'], $arguments);
        CLI::init();

        $logger = $this->createMock(LoggerInterface::class);
        $commands = $this->createMock(Commands::class);
        (new RepairSlugs($logger, $commands))->run([]);
    }

    private function slug(string $table, string $resourceColumn, int $resourceId): string
    {
        $row = $this->db->table($table)
            ->where($resourceColumn, $resourceId)
            ->get()
            ->getRowArray();

        self::assertIsArray($row);

        return (string) $row['slug'];
    }
}
