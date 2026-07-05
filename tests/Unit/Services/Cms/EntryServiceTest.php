<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\EntryServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Config\Services;

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

    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::entryService(false);

        $this->assertInstanceOf(EntryServiceInterface::class, $service);
    }

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
}
