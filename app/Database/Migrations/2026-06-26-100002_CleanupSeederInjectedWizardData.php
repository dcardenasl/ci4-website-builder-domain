<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Removes data injected by the legacy WizardConfigSeeder run:
 *
 * 1. Strips the hardcoded `description` key from every collection's wizard_config.
 *    The description was seeder-injected Spanish text — it is not user data.
 *    The wizard card already shows the collection name; no subtitle is needed.
 *
 * 2. Deletes the `festivales` collection if it has zero entries.
 *    That collection was created entirely by the old seeder (fake placeholder data).
 *    If entries exist it is left untouched and a warning is printed.
 */
class CleanupSeederInjectedWizardData extends Migration
{
    public function up(): void
    {
        // ── 1. Strip hardcoded `description` from every wizard_config ──────────
        $rows = $this->db->table('cms_collections')
            ->where('wizard_config IS NOT NULL')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $raw = $row['wizard_config'] ?? null;
            if (!is_string($raw) || $raw === '') {
                continue;
            }

            $config = json_decode($raw, true);
            if (!is_array($config) || !array_key_exists('description', $config)) {
                continue;
            }

            unset($config['description']);

            $this->db->table('cms_collections')
                ->where('id', $row['id'])
                ->update([
                    'wizard_config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
        }

        // ── 2. Delete seeder-created `festivales` collection if empty ──────────
        $festivales = $this->db->table('cms_collections')
            ->where('collection_key', 'festivales')
            ->get()
            ->getRowArray();

        if ($festivales === null) {
            echo "CleanupMigration: 'festivales' collection not found — skipping.\n";

            return;
        }

        $collectionId = (int) ($festivales['id'] ?? 0);

        $entryCount = $this->db->table('cms_entries')
            ->where('collection_id', $collectionId)
            ->countAllResults();

        if ($entryCount > 0) {
            echo "CleanupMigration: 'festivales' has {$entryCount} real entries — NOT deleting. Remove manually if needed.\n";

            return;
        }

        // No entries — safe to remove (cascade on FK or manual child cleanup)
        $this->db->table('cms_collection_translations')->where('collection_id', $collectionId)->delete();
        $this->db->table('cms_collections')->where('id', $collectionId)->delete();

        echo "CleanupMigration: 'festivales' collection deleted (had 0 entries).\n";
    }

    public function down(): void
    {
        // Descriptions and the festivales collection are not restored on rollback —
        // the seeder must be re-run manually if needed.
        echo "CleanupMigration: down() is a no-op — manual restore required.\n";
    }
}
