<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Config\Services;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

/**
 * Smoke tests for EntryService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class EntryServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testBeforeStoreFillsPublishDefaultsFromCollection(): void
    {
        $collectionId = $this->insertCollection([
            'requires_approval' => 0,
            'default_sitemap_priority' => '0.7',
            'default_changefreq' => 'daily',
        ]);

        $service = Services::entryService(false);
        $method = new \ReflectionMethod($service, 'beforeStore');
        $method->setAccessible(true);

        /** @var array<string, mixed> $data */
        $data = $method->invoke($service, [
            'collection_id' => $collectionId,
            'workflow_status' => 'published',
        ], null);

        $this->assertSame('published', $data['workflow_status']);
        $this->assertNotEmpty($data['published_at']);
        $this->assertNull($data['scheduled_at']);
        $this->assertSame(0, $data['view_count']);
        $this->assertSame(0, $data['sort_order']);
        $this->assertSame(1, $data['is_in_sitemap']);
        $this->assertSame(0.7, $data['sitemap_priority']);
        $this->assertSame('daily', $data['sitemap_changefreq']);
    }

    public function testBeforeStoreSendsPublishAttemptToReviewWhenCollectionRequiresApproval(): void
    {
        $collectionId = $this->insertCollection(['requires_approval' => 1]);

        $service = Services::entryService(false);
        $method = new \ReflectionMethod($service, 'beforeStore');
        $method->setAccessible(true);

        /** @var array<string, mixed> $data */
        $data = $method->invoke($service, [
            'collection_id' => $collectionId,
            'workflow_status' => 'published',
        ], null);

        $this->assertSame('in_review', $data['workflow_status']);
        $this->assertNull($data['published_at']);
    }

    public function testShowIncludesCategoriesAndTags(): void
    {
        $collectionId = $this->insertCollection();
        $entryId = $this->insertEntry($collectionId);
        $categoryId = $this->insertCategory($collectionId);
        $tagId = $this->insertTag();
        $this->linkEntryCategory($entryId, $categoryId, 0);
        $this->linkEntryTag($entryId, $tagId);

        $service = Services::entryService(false);
        $payload = $service->show($entryId, null)->toArray();

        $this->assertSame([
            ['id' => $categoryId, 'sort_order' => 0],
        ], $payload['categories']);
        $this->assertSame([
            ['id' => $tagId],
        ], $payload['tags']);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertCollection(array $overrides = []): int
    {
        $db = Database::connect();
        $payload = array_merge([
            'collection_key' => 'articles-' . bin2hex(random_bytes(3)),
            'collection_type' => 'article',
            'is_active' => 1,
            'requires_approval' => 0,
            'enables_categories' => 1,
            'enables_tags' => 1,
            'default_sitemap_priority' => '0.5',
            'default_changefreq' => 'weekly',
            'sort_order' => 0,
        ], $overrides);

        $db->table('cms_collections')->insert($payload);

        return (int) $db->insertID();
    }

    private function insertEntry(int $collectionId): int
    {
        $db = Database::connect();
        $db->table('cms_entries')->insert([
            'collection_id' => $collectionId,
            'author_id' => null,
            'workflow_status' => 'draft',
            'published_at' => null,
            'scheduled_at' => null,
            'is_featured' => 0,
            'view_count' => 0,
            'sort_order' => 0,
            'sitemap_priority' => '0.5',
            'sitemap_changefreq' => 'weekly',
            'is_in_sitemap' => 1,
        ]);

        return (int) $db->insertID();
    }

    private function insertCategory(int $collectionId): int
    {
        $db = Database::connect();
        $db->table('cms_categories')->insert([
            'collection_id' => $collectionId,
            'parent_id' => null,
            'sort_order' => 0,
            'is_active' => 1,
        ]);

        return (int) $db->insertID();
    }

    private function insertTag(): int
    {
        $db = Database::connect();
        $db->table('cms_tags')->insert([
            'is_active' => 1,
        ]);

        return (int) $db->insertID();
    }

    private function linkEntryCategory(int $entryId, int $categoryId, int $sortOrder): void
    {
        Database::connect()->table('cms_entry_categories')->insert([
            'entry_id' => $entryId,
            'category_id' => $categoryId,
            'sort_order' => $sortOrder,
        ]);
    }

    private function linkEntryTag(int $entryId, int $tagId): void
    {
        Database::connect()->table('cms_entry_tags')->insert([
            'entry_id' => $entryId,
            'tag_id' => $tagId,
        ]);
    }

    public function testDestroyInvalidatesCache(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn((object) ['id' => 10]);
        $repository->expects($this->once())
            ->method('setEntityContext')
            ->with(10, $this->isInstanceOf(\stdClass::class));
        $repository->expects($this->once())
            ->method('delete')
            ->with(10)
            ->willReturn(true);

        $responseMapper = $this->createMock(ResponseMapperInterface::class);
        $cacheMock = $this->createMock(\App\Libraries\Cms\CacheInvalidationClient::class);
        $cacheMock->expects($this->once())
            ->method('invalidate')
            ->with(['entries']);
        $referenceSynchronizer = $this->createMock(\App\Libraries\Cms\FileReferenceSynchronizer::class);
        $referenceSynchronizer->expects($this->once())
            ->method('removeResourceReferences')
            ->with('entry', 10);

        $service = new \App\Services\Cms\EntryService(
            $repository,
            $responseMapper,
            $this->createMock(\App\Libraries\Cms\SlugRedirectRecorder::class),
            $cacheMock,
            $this->createMock(\App\Libraries\Cms\FileUrlResolver::class),
            $referenceSynchronizer,
            $this->createMock(\App\Libraries\Cms\TranslationResolver::class),
            $this->createMock(\App\Services\Cms\PublicEntryReader::class),
            $this->createMock(\App\Libraries\Cms\EntryTaxonomyPivotResolver::class),
            $this->createMock(\App\Services\Cms\EntryBlockTemplateInitializer::class)
        );
        $result = $service->destroy(10, null);

        $this->assertTrue($result);
    }
}
