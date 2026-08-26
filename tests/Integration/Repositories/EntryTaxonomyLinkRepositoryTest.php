<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Models\EntryCategoryModel;
use App\Models\EntryTagModel;
use App\Repositories\Cms\EntryCategoryLinkRepository;
use App\Repositories\Cms\EntryTagLinkRepository;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class EntryTaxonomyLinkRepositoryTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $entryId;
    private int $otherEntryId;
    /** @var list<int> */
    private array $categoryIds = [];
    /** @var list<int> */
    private array $tagIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        foreach (
            ['cms_entry_categories', 'cms_entry_tags', 'cms_entries', 'cms_categories', 'cms_tags', 'cms_collections'] as $table
        ) {
            $this->db->query("DELETE FROM `{$table}`");
        }
        $this->db->enableForeignKeyChecks();

        $this->db->table('cms_collections')->insert(['collection_key' => 'news']);
        $collectionId = $this->db->insertID();

        $this->db->table('cms_entries')->insert(['collection_id' => $collectionId]);
        $this->entryId = $this->db->insertID();

        $this->db->table('cms_entries')->insert(['collection_id' => $collectionId]);
        $this->otherEntryId = $this->db->insertID();

        foreach ([1, 2, 3] as $ignored) {
            $this->db->table('cms_categories')->insert(['collection_id' => $collectionId]);
            $this->categoryIds[] = (int) $this->db->insertID();

            $this->db->table('cms_tags')->insert(['is_active' => 1]);
            $this->tagIds[] = (int) $this->db->insertID();
        }
    }

    private function categoryRepository(): EntryCategoryLinkRepository
    {
        return new EntryCategoryLinkRepository(new EntryCategoryModel($this->db));
    }

    private function tagRepository(): EntryTagLinkRepository
    {
        return new EntryTagLinkRepository(new EntryTagModel($this->db));
    }

    /** @return list<array<string, mixed>> */
    private function categoryRows(int $entryId): array
    {
        $result = $this->db->table('cms_entry_categories')
            ->where('entry_id', $entryId)
            ->orderBy('sort_order', 'ASC')
            ->get();

        return $result === false ? [] : $result->getResultArray();
    }

    public function testCategoriesArePersistedInTheGivenOrder(): void
    {
        [$first, $second, $third] = $this->categoryIds;

        $this->categoryRepository()->replaceForEntry($this->entryId, [$third, $first, $second]);

        $rows = $this->categoryRows($this->entryId);

        $this->assertSame(
            [$third, $first, $second],
            array_map(static fn (array $row): int => (int) $row['category_id'], $rows)
        );
        $this->assertSame([0, 1, 2], array_map(static fn (array $row): int => (int) $row['sort_order'], $rows));
    }

    public function testReplacingIsAFullSetReplacement(): void
    {
        [$first, $second, $third] = $this->categoryIds;
        $repository = $this->categoryRepository();

        $repository->replaceForEntry($this->entryId, [$first, $second]);
        $repository->replaceForEntry($this->entryId, [$third]);

        $this->assertSame(
            [$third],
            array_map(static fn (array $row): int => (int) $row['category_id'], $this->categoryRows($this->entryId))
        );
    }

    public function testEmptyListClearsTheEntrysLinks(): void
    {
        $repository = $this->categoryRepository();
        $repository->replaceForEntry($this->entryId, $this->categoryIds);

        $repository->replaceForEntry($this->entryId, []);

        $this->assertSame([], $this->categoryRows($this->entryId));
    }

    public function testOtherEntriesLinksAreUntouched(): void
    {
        [$first, $second] = $this->categoryIds;
        $repository = $this->categoryRepository();

        $repository->replaceForEntry($this->otherEntryId, [$first, $second]);
        $repository->replaceForEntry($this->entryId, []);

        $this->assertCount(2, $this->categoryRows($this->otherEntryId));
    }

    public function testTagsAreReplacedAsAnUnorderedSet(): void
    {
        [$first, $second, $third] = $this->tagIds;
        $repository = $this->tagRepository();

        $repository->replaceForEntry($this->entryId, [$first, $second]);
        $repository->replaceForEntry($this->entryId, [$second, $third]);

        $result = $this->db->table('cms_entry_tags')->where('entry_id', $this->entryId)->get();
        $stored = $result === false
            ? []
            : array_map(static fn (array $row): int => (int) $row['tag_id'], $result->getResultArray());

        sort($stored);
        $expected = [$second, $third];
        sort($expected);

        $this->assertSame($expected, $stored);
    }
}
