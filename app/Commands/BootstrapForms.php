<?php

declare(strict_types=1);

namespace App\Commands;

use App\Database\Seeds\CmsFormSeeder;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark cms:bootstrap-forms
 *
 * Idempotent command that seeds the default contact form definition into
 * cms_forms, cms_form_translations, cms_form_fields, and cms_form_field_translations.
 *
 * Run after migrations:
 *   php spark migrate
 *   php spark cms:bootstrap-forms
 *
 * For post-install permission sync, also run:
 *   php spark domain:sync-permissions --admin-token=<jwt>
 */
class BootstrapForms extends BaseCommand
{
    protected $group       = 'Cms';
    protected $name        = 'cms:bootstrap-forms';
    protected $description = 'Seed the default CMS dynamic forms (contact). Idempotent.';
    protected $usage       = 'cms:bootstrap-forms';

    public function run(array $params): int
    {
        CLI::write('[cms:bootstrap-forms] Seeding CMS forms...', 'yellow');

        try {
            $seeder = new CmsFormSeeder(config('Database'));
            $seeder->run();
            CLI::write('[cms:bootstrap-forms] Done.', 'green');
            return 0;
        } catch (\Throwable $e) {
            CLI::error('[cms:bootstrap-forms] Failed: ' . $e->getMessage());
            return 1;
        }
    }
}
