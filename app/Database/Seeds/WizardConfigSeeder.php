<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class WizardConfigSeeder extends Seeder
{
    /**
     * wizard_config to apply to existing collections, keyed by collection_key.
     * This seeder ONLY updates collections that already exist — it never creates new ones.
     * Collections belong to the application; this seeder only configures the wizard UX.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $configs = [
        'noticias' => [
            'icon'  => '📰',
            'steps' => [
                [
                    'step_title' => '¿Cuál es el titular?',
                    'step_hint'  => 'El título que aparecerá en el listado y en la página.',
                    'fields'     => [
                        [
                            'key'         => 'title',
                            'label'       => 'Titular',
                            'type'        => 'text',
                            'required'    => true,
                            'placeholder' => 'Ej: Se inaugura nueva temporada',
                        ],
                    ],
                ],
                [
                    'step_title' => 'Imagen de portada',
                    'step_hint'  => 'Una foto que acompañe la noticia.',
                    'fields'     => [
                        [
                            'key'      => 'featured_image',
                            'label'    => 'Imagen',
                            'type'     => 'image',
                            'required' => false,
                        ],
                    ],
                ],
                [
                    'step_title' => 'Resumen',
                    'step_hint'  => 'Una o dos frases que aparecerán en el listado.',
                    'fields'     => [
                        [
                            'key'         => 'excerpt',
                            'label'       => 'Resumen',
                            'type'        => 'textarea',
                            'required'    => false,
                            'placeholder' => 'Breve descripción de la noticia',
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->configs as $key => $config) {
            $exists = $this->db->table('cms_collections')
                ->where('collection_key', $key)
                ->countAllResults() > 0;

            if (!$exists) {
                echo "WizardConfigSeeder: colección '{$key}' no existe — saltando (no se crean colecciones desde este seeder).\n";
                continue;
            }

            $this->db->table('cms_collections')
                ->where('collection_key', $key)
                ->update(['wizard_config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

            echo "WizardConfigSeeder: wizard_config aplicado a '{$key}'.\n";
        }
    }
}
