<?php

declare(strict_types=1);

namespace App\Jobs;

use CodeIgniter\I18n\Time;
use Config\Database;
use Config\Services;
use dcardenasl\Ci4ApiCore\Queue\Job;
use Throwable;

class ScheduledPublishingJob extends Job
{
    /**
     * Handle the scheduled publishing job.
     * Searches for pages and entries with scheduled_at <= now() and updates their status to published.
     *
     * @return void
     */
    public function handle(): void
    {
        $db = Database::connect();
        $now = Time::now()->toDateTimeString();

        log_message('info', 'ScheduledPublishingJob: Starting execution at ' . $now);

        $db->transStart();

        try {
            // 1. Process pages
            $pageModel = model(\App\Models\PageModel::class);
            $pages = $pageModel->where('status !=', 'published')
                ->where('scheduled_at IS NOT NULL')
                ->where('scheduled_at <=', $now)
                ->findAll();

            $pageService = Services::pageService();

            foreach ($pages as $page) {
                log_message('info', 'ScheduledPublishingJob: Publishing page ID ' . $page->id);
                $pageModel->update($page->id, [
                    'status'       => 'published',
                    'published_at' => $now,
                ]);

                // Create version snapshot for page
                $pageService->createVersionSnapshot((int) $page->id, 'Published via ScheduledPublishingJob');
            }

            // 2. Process entries
            $entryModel = model(\App\Models\EntryModel::class);
            $entries = $entryModel->where('workflow_status !=', 'published')
                ->where('scheduled_at IS NOT NULL')
                ->where('scheduled_at <=', $now)
                ->findAll();

            $entryService = Services::entryService();

            foreach ($entries as $entry) {
                log_message('info', 'ScheduledPublishingJob: Publishing entry ID ' . $entry->id);
                $entryModel->update($entry->id, [
                    'workflow_status' => 'published',
                    'published_at'    => $now,
                ]);

                // Create version snapshot for entry
                $entryService->createVersionSnapshot((int) $entry->id, 'Published via ScheduledPublishingJob');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed while running ScheduledPublishingJob');
            }

            log_message('info', sprintf(
                'ScheduledPublishingJob: Completed successfully. Published %d pages and %d entries.',
                count($pages),
                count($entries)
            ));
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'ScheduledPublishingJob: Error processing scheduled publishing: ' . $e->getMessage());
            throw $e;
        }
    }
}
