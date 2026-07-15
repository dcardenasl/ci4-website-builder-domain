<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FixLegalMenuPageIds extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'legal-menu:fix-page-ids';
    protected $description = 'Fix legal menu items to point to correct page IDs (Spanish pages)';
    protected $usage = 'php spark legal-menu:fix-page-ids';

    public function run(array $params = []): void
    {
        $db = \Config\Database::connect();

        // Map menu item IDs to correct Spanish page IDs
        $fixes = [
            63 => 54,  // aviso-legal-chile item: page 38 (legal-notice) → 54 (aviso-legal)
            64 => 55,  // politica-privacidad item: page 39 (privacy-policy) → 55 (politica-privacidad)
            65 => 56,  // politica-cookies item: page 40 (cookie-policy) → 56 (politica-cookies)
            66 => 57,  // terminos-servicio item: page 41 (terms-of-service) → 57 (terminos-servicio)
        ];

        foreach ($fixes as $itemId => $newPageId) {
            $db->table('cms_menu_items')
                ->where('id', $itemId)
                ->update([
                    'page_id'   => $newPageId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            CLI::write(sprintf('✓ Updated menu item %d: page_id = %d', $itemId, $newPageId), 'green');
        }

        // Invalidate cache
        if (class_exists(\App\Libraries\Cms\CacheInvalidationClient::class)) {
            service('cacheInvalidationClient')->invalidate(['menus']);
            CLI::write('✓ Cache invalidated', 'green');
        }

        CLI::newLine();
        CLI::write('Legal menu page IDs fixed successfully!', 'green');
    }
}
