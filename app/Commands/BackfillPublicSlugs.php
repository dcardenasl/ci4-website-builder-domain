<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

/** Populate generic public slugs from the current resource translation tables. */
final class BackfillPublicSlugs extends BaseCommand
{
    protected $group = 'CMS';
    protected $name = 'cms:backfill-public-slugs';
    protected $description = 'Project existing collection translation slugs into public_slugs.';

    public function run(array $params): int
    {
        $count = Services::collectionPublicSlugProjection()->backfill();
        CLI::write("Public slug backfill complete. Collections projected: {$count}.", 'green');

        return EXIT_SUCCESS;
    }
}
