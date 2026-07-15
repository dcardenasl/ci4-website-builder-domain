<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CleanAllLegalPages extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'legal-pages:clean-all';
    protected $description = 'Delete ALL legal pages (old Spain-based and legal menu) to prepare for Chile seeding';
    protected $usage = 'php spark legal-pages:clean-all';

    public function run(array $params = []): void
    {
        $db = \Config\Database::connect();

        // Slugs to delete: both Spanish and English versions from old seeder
        $slugsToDelete = [
            'aviso-legal', 'legal-notice',
            'politica-privacidad', 'privacy-policy',
            'politica-cookies', 'cookie-policy',
            'terminos-servicio', 'terms-of-service',
            'derechos-datos', 'data-rights',
            'transparencia', 'transparency',
            'accesibilidad', 'accessibility',
            // Also old Chile-specific slugs
            'aviso-legal-chile',
            'politica-privacidad-chile',
            'politica-cookies-chile',
            'terminos-servicio-chile',
        ];

        // Find all pages with these slugs
        $result = $db->table('cms_page_translations')
            ->distinct()
            ->select('cms_page_translations.page_id')
            ->whereIn('slug', $slugsToDelete)
            ->get();
        $pageIds = $result ? $result->getResultArray() : [];

        if (empty($pageIds)) {
            CLI::write('✓ No legal pages found to delete', 'green');
            return;
        }

        $pageIdsList = array_column($pageIds, 'page_id');
        $deletedPages = count($pageIdsList);

        // Delete page blocks
        $deletedBlocks = $db->table('cms_page_blocks')
            ->whereIn('page_id', $pageIdsList)
            ->delete();

        // Delete page translations
        $deletedTranslations = $db->table('cms_page_translations')
            ->whereIn('page_id', $pageIdsList)
            ->delete();

        // Delete pages themselves (soft delete)
        $db->table('cms_pages')
            ->whereIn('id', $pageIdsList)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        CLI::write(sprintf('✓ Deleted %d legal pages', $deletedPages), 'green');
        CLI::write(sprintf('✓ Deleted %d page blocks', $deletedBlocks), 'green');
        CLI::write(sprintf('✓ Deleted %d page translations', $deletedTranslations), 'green');

        // Clean legal menu
        $result = $db->table('cms_menus')
            ->where('menu_key', 'legal')
            ->get();
        $menu = $result ? $result->getRow() : null;

        if ($menu) {
            $deletedItems = $db->table('cms_menu_items')
                ->where('menu_id', $menu->id)
                ->delete();
            CLI::write(sprintf('✓ Deleted %d legal menu items', $deletedItems), 'green');
        }

        // Invalidate cache
        if (class_exists(\App\Libraries\Cms\CacheInvalidationClient::class)) {
            service('cacheInvalidationClient')->invalidate(['pages', 'menus']);
            CLI::write('✓ Cache invalidated', 'green');
        }

        CLI::newLine();
        CLI::write('All legal pages cleaned. Ready for fresh seeding with Chile content.', 'green');
    }
}
