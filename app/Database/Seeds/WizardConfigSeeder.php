<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class WizardConfigSeeder extends Seeder
{
    public function run(): void
    {
        $preset = [
            'block_template' => [
                'version' => '1.0',
                'blocks' => [
                    [
                        'block_key' => 'rich_text',
                        'label' => 'Titular',
                        'help_text' => 'Bloque principal de la noticia',
                        'required' => true,
                        'locked' => true,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 1,
                    ],
                    [
                        'block_key' => 'image',
                        'label' => 'Imagen de portada',
                        'help_text' => 'Acompaña la noticia con una imagen',
                        'required' => false,
                        'locked' => false,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 2,
                    ],
                ],
            ],
            'wizard_config' => [
                'type' => 'news',
                'steps' => [
                    ['step_title' => 'Titular', 'step_hint' => 'Título visible para la noticia', 'fields' => [['key' => 'title', 'label' => 'Titular', 'type' => 'text', 'required' => true]]],
                    ['step_title' => 'Resumen', 'step_hint' => 'Una breve bajada informativa', 'fields' => [['key' => 'excerpt', 'label' => 'Resumen', 'type' => 'textarea', 'required' => false]]],
                ],
            ],
        ];

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
