<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CleanupSpanishLegalPages extends Seeder
{
    public function run(): void
    {
        $slugs = [
            'aviso-legal', 'politica-privacidad', 'politica-cookies',
            'derechos-datos', 'terminos-servicio', 'transparencia',
            'accesibilidad'
        ];

        $translations = $this->db->table('cms_page_translations')
            ->whereIn('slug', $slugs)
            ->get()
            ->getResultArray();

        $pageIds = array_unique(array_map(fn ($t) => $t['page_id'], $translations));

        echo "Deleting " . count($pageIds) . " Spanish legal pages...\n";

        foreach ($pageIds as $pageId) {
            // Delete block instances
            $instances = $this->db->table('cms_block_instances')
                ->where('owner_type', 'page')
                ->where('owner_id', $pageId)
                ->get()
                ->getResultArray();

            $instanceIds = array_column($instances, 'id');
            if ($instanceIds) {
                $this->db->table('cms_block_instance_translations')
                    ->whereIn('instance_id', $instanceIds)
                    ->delete();
                $this->db->table('cms_block_instances')
                    ->whereIn('id', $instanceIds)
                    ->delete();
            }

            // Delete page translations
            $this->db->table('cms_page_translations')
                ->where('page_id', $pageId)
                ->delete();

            // Delete page
            $this->db->table('cms_pages')
                ->where('id', $pageId)
                ->delete();
        }

        echo "Done. Deleted " . count($pageIds) . " pages.\n";
    }
}
