<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\CollectionBlockPresets;
use CodeIgniter\Database\Seeder;

/**
 * Repairs the `block_template`/`wizard_config` of any already-seeded starter
 * collection (news, portfolio) so it matches the canonical preset in
 * `CollectionBlockPresets`.
 *
 * This is site bootstrap data, not structural CMS data. A fresh install without
 * this seeder should still expose the wizard with dynamic setup states.
 *
 * On a from-scratch install this is a no-op safety net — `NewsCollectionSeeder`
 * / `PortfolioCollectionSeeder` already write the correct preset themselves.
 * Its actual job is idempotently repairing a database where the collection
 * row already exists with a stale/hand-edited `block_template` (the scenario
 * that shipped a 6-block Noticias/Portafolio template — see
 * `CollectionBlockPresets`'s docblock). Safe to re-run any time:
 * `php spark db:seed WizardConfigSeeder`.
 */
class WizardConfigSeeder extends Seeder
{
    public function run(): void
    {
        $hasTypeColumn = $this->db->fieldExists('collection_type', 'cms_collections');
        $collectionKeys = CollectionBlockPresets::collectionKeys();
        $updated = 0;

        foreach (CollectionBlockPresets::all() as $collectionType => $preset) {
            $query = $this->db->table('cms_collections');
            if ($hasTypeColumn) {
                $query->groupStart()
                    ->where('collection_type', $collectionType)
                    ->orWhere('collection_key', $collectionKeys[$collectionType])
                    ->groupEnd();
            } else {
                $query->where('collection_key', $collectionKeys[$collectionType]);
            }

            $rows = $query->get()->getResultArray();

            foreach ($rows as $row) {
                $this->db->table('cms_collections')->where('id', (int) $row['id'])->update([
                    'collection_type' => $collectionType,
                    'block_template' => json_encode($preset['block_template'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'wizard_config' => json_encode($preset['wizard_config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $updated++;
            }
        }

        if ($updated === 0) {
            echo "WizardConfigSeeder: no matching collection found, skipping.\n";
            return;
        }

        echo "WizardConfigSeeder: preset snapshot applied to {$updated} matching collection(s).\n";
    }
}
