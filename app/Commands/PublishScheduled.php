<?php

declare(strict_types=1);

namespace App\Commands;

use App\Jobs\ScheduledPublishingJob;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class PublishScheduled extends BaseCommand
{
    protected $group = 'CMS';
    protected $name = 'cms:publish-scheduled';
    protected $description = 'Publishes scheduled CMS pages and entries whose publication date has arrived.';

    protected $usage = 'cms:publish-scheduled [options]';
    protected $options = [
        '--run-now' => 'Run the publishing job synchronously immediately instead of pushing it to the queue.',
    ];

    public function run(array $params): void
    {
        $runNow = array_key_exists('run-now', $params) || CLI::getOption('run-now');

        if ($runNow) {
            CLI::write('Running scheduled publishing job synchronously...', 'yellow');
            try {
                $job = new ScheduledPublishingJob();
                $job->handle();
                CLI::write('Scheduled publishing completed successfully.', 'green');
            } catch (\Throwable $e) {
                CLI::error('Scheduled publishing failed: ' . $e->getMessage());
            }
        } else {
            CLI::write('Pushing scheduled publishing job to the queue...', 'yellow');
            try {
                $queueManager = Services::queueManager();
                $jobId = $queueManager->push(ScheduledPublishingJob::class, [], 'default');
                CLI::write("Job pushed successfully to queue. Job ID: {$jobId}", 'green');
            } catch (\Throwable $e) {
                CLI::error('Failed to push job to queue: ' . $e->getMessage());
            }
        }
    }
}
