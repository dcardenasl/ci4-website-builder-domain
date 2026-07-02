<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Libraries\Cms\CollectionPresetResolver;
use CodeIgniter\Database\Seeder;

/**
 * Backfills preset snapshots for existing collections using the shared
 * server-side catalog. This keeps old data aligned with the runtime contract
 * without duplicating the preset definitions in the seeder.
 */
class WizardConfigSeeder extends Seeder
{
    public function run(): void
    {
        $preset = CollectionPresetResolver::resolve('news');

        $query = $this->db->table('cms_collections');
        if ($this->db->fieldExists('collection_type', 'cms_collections')) {
            $query->groupStart()
                ->where('collection_type', 'news')
                ->orWhere('collection_key', 'noticias')
                ->groupEnd();
        } else {
            $query->where('collection_key', 'noticias');
        }

        $rows = $query->get()->getResultArray();

        if ($rows === []) {
            echo "WizardConfigSeeder: no matching collection found, skipping.\n";
            return;
        }

        foreach ($rows as $row) {
            $query = $this->db->table('cms_collections')->where('id', (int) $row['id']);
            $query->update([
                'collection_type' => 'news',
                'block_template' => json_encode($preset['block_template'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'wizard_config' => json_encode($preset['wizard_config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        echo "WizardConfigSeeder: preset snapshot applied to matching collection(s).\n";
    }
}
