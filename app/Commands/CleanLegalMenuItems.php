<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CleanLegalMenuItems extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'legal-menu:clean';
    protected $description = 'Delete all legal menu items to prepare for fresh seeding';
    protected $usage = 'php spark legal-menu:clean';

    public function run(array $params = []): void
    {
        $db = \Config\Database::connect();

        // Find the legal menu
        $result = $db->table('cms_menus')
            ->where('menu_key', 'legal')
            ->get();
        $menu = $result ? $result->getRow() : null;

        if (!$menu) {
            CLI::write('✓ No legal menu found', 'green');
            return;
        }

        // Delete all items for this menu
        $deletedCount = $db->table('cms_menu_items')
            ->where('menu_id', $menu->id)
            ->delete();

        CLI::write(sprintf('✓ Deleted %d legal menu items', $deletedCount), 'green');
        CLI::newLine();
        CLI::write('Legal menu cleaned. Ready for fresh seeding.', 'green');
    }
}
