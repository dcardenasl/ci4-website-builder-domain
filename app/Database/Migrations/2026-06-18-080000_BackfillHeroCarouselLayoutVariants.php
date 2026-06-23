<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillHeroCarouselLayoutVariants extends Migration
{
    public function up(): void
    {
        $this->updateHeroSchema($this->heroSchema());
        $this->db->query(
            "UPDATE cms_block_instances i
             JOIN cms_content_blocks b ON b.id = i.block_id
             SET i.block_config = JSON_SET(
                 COALESCE(i.block_config, JSON_OBJECT()),
                 '$.caption_position', 'below',
                 '$.controls_position', 'below',
                 '$.overlay_opacity', '0'
             )
             WHERE b.block_key = 'hero_slider'"
        );
    }

    public function down(): void
    {
        $this->updateHeroSchema($this->previousHeroSchema());
        $this->db->query(
            "UPDATE cms_block_instances i
             JOIN cms_content_blocks b ON b.id = i.block_id
             SET i.block_config = JSON_REMOVE(
                 JSON_SET(
                     COALESCE(i.block_config, JSON_OBJECT()),
                     '$.overlay_opacity', '40'
                 ),
                 '$.caption_position',
                 '$.controls_position'
             )
             WHERE b.block_key = 'hero_slider'"
        );
    }

    private function updateHeroSchema(array $schema): void
    {
        $schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($schemaJson)) {
            return;
        }

        $this->db->query(
            "UPDATE cms_content_blocks
             SET schema_definition = ?
             WHERE block_key = 'hero_slider'",
            [$schemaJson]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function heroSchema(): array
    {
        return [
            'fields' => [
                'image'     => ['type' => 'file',   'label' => 'Imagen',           'required' => true],
                'heading'   => ['type' => 'string', 'label' => 'Título',           'required' => true],
                'subtitle'  => ['type' => 'string', 'label' => 'Subtítulo',        'required' => false],
                'cta_label' => ['type' => 'string', 'label' => 'Texto del botón',  'required' => false],
                'cta_url'   => ['type' => 'url',    'label' => 'URL del botón',    'required' => false],
            ],
            'config_fields' => [
                'autoplay' => ['type' => 'boolean', 'label' => 'Reproducción automática', 'required' => false, 'default' => true],
                'interval' => ['type' => 'number', 'label' => 'Intervalo (ms)', 'required' => false, 'default' => 6000],
                'overlay_opacity' => [
                    'type'     => 'select',
                    'label'    => 'Opacidad del overlay',
                    'options'  => ['0', '20', '40', '60', '80'],
                    'default'  => '0',
                    'required' => false,
                ],
                'caption_position' => [
                    'type'     => 'select',
                    'label'    => 'Posición del texto',
                    'options'  => ['below', 'overlay_top', 'overlay_bottom', 'hide'],
                    'default'  => 'below',
                    'required' => false,
                ],
                'controls_position' => [
                    'type'     => 'select',
                    'label'    => 'Posición de controles',
                    'options'  => ['below', 'overlay_bottom'],
                    'default'  => 'below',
                    'required' => false,
                ],
                'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function previousHeroSchema(): array
    {
        return [
            'fields' => [
                'image'     => ['type' => 'file',   'label' => 'Imagen',           'required' => true],
                'heading'   => ['type' => 'string', 'label' => 'Título',           'required' => true],
                'subtitle'  => ['type' => 'string', 'label' => 'Subtítulo',        'required' => false],
                'cta_label' => ['type' => 'string', 'label' => 'Texto del botón',  'required' => false],
                'cta_url'   => ['type' => 'url',    'label' => 'URL del botón',    'required' => false],
            ],
            'config_fields' => [
                'autoplay' => ['type' => 'boolean', 'label' => 'Reproducción automática', 'required' => false, 'default' => true],
                'interval' => ['type' => 'number', 'label' => 'Intervalo (ms)', 'required' => false, 'default' => 6000],
                'overlay_opacity' => [
                    'type'     => 'select',
                    'label'    => 'Opacidad del overlay',
                    'options'  => ['0', '20', '40', '60', '80'],
                    'default'  => '40',
                    'required' => false,
                ],
                'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
            ],
        ];
    }
}
