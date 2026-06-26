<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWizardFieldsToCms extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('cms_collections', [
            'wizard_config' => [
                'type'    => 'JSON',
                'null'    => true,
                'default' => null,
                'after'   => 'block_template',
                'comment' => 'Wizard step configuration for non-technical users',
            ],
        ]);

        $this->forge->addColumn('cms_entries', [
            'wizard_extra' => [
                'type'    => 'JSON',
                'null'    => true,
                'default' => null,
                'after'   => 'sort_order',
                'comment' => 'Extra fields captured by wizard (not in standard entry schema)',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('cms_collections', 'wizard_config');
        $this->forge->dropColumn('cms_entries', 'wizard_extra');
    }
}
