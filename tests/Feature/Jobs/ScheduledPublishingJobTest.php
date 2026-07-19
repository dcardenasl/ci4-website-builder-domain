<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\ScheduledPublishingJob;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Fixtures\CmsFixtureFactory;

/**
 * @internal
 */
final class ScheduledPublishingJobTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $defaultLanguageId;
    private int $collectionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_page_versions`");
        $this->db->query("DELETE FROM `cms_page_translations`");
        $this->db->query("DELETE FROM `cms_pages`");
        $this->db->query("DELETE FROM `cms_entry_versions`");
        $this->db->query("DELETE FROM `cms_entry_translations`");
        $this->db->query("DELETE FROM `cms_entries`");
        $this->db->query("DELETE FROM `cms_collections`");
        $this->db->query("DELETE FROM `cms_languages`");
        $this->db->enableForeignKeyChecks();

        $this->defaultLanguageId = (new CmsFixtureFactory($this->db, self::class))->languages(1)[0]['id'];

        // Seed collection (required for entries)
        $this->db->table('cms_collections')->insert([
            'collection_key' => 'blog',
            'is_active'      => 1,
            'sort_order'     => 1,
        ]);
        $this->collectionId = $this->db->insertID();
    }

    public function testPageScheduledInPastGetsPublished(): void
    {
        $pastTime = Time::now()->subMinutes(10)->toDateTimeString();

        $this->db->table('cms_pages')->insert([
            'status'        => 'draft',
            'page_type'     => 'generic',
            'scheduled_at'  => $pastTime,
            'sort_order'    => 1,
            'is_in_sitemap' => 1,
        ]);
        $pageId = $this->db->insertID();

        // Run publishing job
        $job = new ScheduledPublishingJob();
        $job->handle();

        // Assert page status is now published
        $page = $this->db->table('cms_pages')->where('id', $pageId)->get()->getRow();
        $this->assertSame('published', $page->status);
        $this->assertNotNull($page->published_at);

        // Assert version snapshot was created
        $version = $this->db->table('cms_page_versions')->where('page_id', $pageId)->get()->getRow();
        $this->assertNotNull($version);
        $this->assertSame(1, (int) $version->version_number);
    }

    public function testPageScheduledInFutureRemainsDraft(): void
    {
        $futureTime = Time::now()->addDays(1)->toDateTimeString();

        $this->db->table('cms_pages')->insert([
            'status'        => 'draft',
            'page_type'     => 'generic',
            'scheduled_at'  => $futureTime,
            'sort_order'    => 1,
            'is_in_sitemap' => 1,
        ]);
        $pageId = $this->db->insertID();

        // Run publishing job
        $job = new ScheduledPublishingJob();
        $job->handle();

        // Assert page status is still draft
        $page = $this->db->table('cms_pages')->where('id', $pageId)->get()->getRow();
        $this->assertSame('draft', $page->status);
        $this->assertNull($page->published_at);
    }

    public function testEntryScheduledInPastGetsPublished(): void
    {
        $pastTime = Time::now()->subMinutes(10)->toDateTimeString();

        $this->db->table('cms_entries')->insert([
            'collection_id'   => $this->collectionId,
            'workflow_status' => 'draft',
            'scheduled_at'    => $pastTime,
            'is_featured'     => 0,
            'view_count'      => 0,
            'sort_order'      => 1,
            'is_in_sitemap'   => 1,
        ]);
        $entryId = $this->db->insertID();

        // Run publishing job
        $job = new ScheduledPublishingJob();
        $job->handle();

        // Assert entry workflow_status is now published
        $entry = $this->db->table('cms_entries')->where('id', $entryId)->get()->getRow();
        $this->assertSame('published', $entry->workflow_status);
        $this->assertNotNull($entry->published_at);

        // Assert version snapshot was created
        $version = $this->db->table('cms_entry_versions')->where('entry_id', $entryId)->get()->getRow();
        $this->assertNotNull($version);
        $this->assertSame(1, (int) $version->version_number);
    }

    public function testEntryScheduledInFutureRemainsDraft(): void
    {
        $futureTime = Time::now()->addDays(1)->toDateTimeString();

        $this->db->table('cms_entries')->insert([
            'collection_id'   => $this->collectionId,
            'workflow_status' => 'draft',
            'scheduled_at'    => $futureTime,
            'is_featured'     => 0,
            'view_count'      => 0,
            'sort_order'      => 1,
            'is_in_sitemap'   => 1,
        ]);
        $entryId = $this->db->insertID();

        // Run publishing job
        $job = new ScheduledPublishingJob();
        $job->handle();

        // Assert entry workflow_status is still draft
        $entry = $this->db->table('cms_entries')->where('id', $entryId)->get()->getRow();
        $this->assertSame('draft', $entry->workflow_status);
        $this->assertNull($entry->published_at);
    }

    public function testCommandExecutesJobSynchronously(): void
    {
        $pastTime = Time::now()->subMinutes(5)->toDateTimeString();

        $this->db->table('cms_pages')->insert([
            'status'        => 'draft',
            'page_type'     => 'generic',
            'scheduled_at'  => $pastTime,
            'sort_order'    => 1,
            'is_in_sitemap' => 1,
        ]);
        $pageId = $this->db->insertID();

        // Run CLI command synchronously
        command('cms:publish-scheduled --run-now');

        // Assert page status is now published
        $page = $this->db->table('cms_pages')->where('id', $pageId)->get()->getRow();
        $this->assertSame('published', $page->status);
        $this->assertNotNull($page->published_at);
    }

    public function testCommandPushesToQueue(): void
    {
        // Truncate jobs table first
        $this->db->query("DELETE FROM `jobs`");

        // Run CLI command (pushes to queue)
        command('cms:publish-scheduled');

        // Assert job is in the jobs table
        $jobRecord = $this->db->table('jobs')->get()->getRow();
        $this->assertNotNull($jobRecord);
        $this->assertSame('default', $jobRecord->queue);

        $payload = json_decode($jobRecord->payload, true);
        $this->assertSame(ScheduledPublishingJob::class, $payload['job']);
    }
}
