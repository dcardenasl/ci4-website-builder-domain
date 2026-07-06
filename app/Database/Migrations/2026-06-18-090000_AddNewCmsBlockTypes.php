<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNewCmsBlockTypes extends Migration
{
    private array $blocks = [
        [
            'block_key' => 'accordion',
            'name' => 'Acordeón (Contenedor)',
            'description' => 'Contenedor para agrupar elementos desplegables como preguntas, requisitos, programa o detalles.',
            'category' => 'content',
            'icon' => 'list',
            'schema_definition' => [
                'fields' => [],
                'config_fields' => [
                    'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                ],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 1,
            'is_active' => 1,
            'sort_order' => 80,
        ],
        [
            'block_key' => 'accordion_item',
            'name' => 'Elemento de Acordeón',
            'description' => 'Elemento desplegable individual dentro de un acordeón.',
            'category' => 'content',
            'icon' => 'help-circle',
            'schema_definition' => [
                'fields' => [
                    'title' => ['type' => 'string', 'label' => 'Título', 'required' => true],
                    'content' => ['type' => 'richtext', 'label' => 'Contenido', 'required' => true],
                ],
                'config_fields' => [
                    'is_open' => ['type' => 'boolean', 'label' => 'Abierto por defecto', 'required' => false, 'default' => false],
                ],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 81,
        ],
        [
            'block_key' => 'cards_grid',
            'name' => 'Grilla de Tarjetas (Contenedor)',
            'description' => 'Contenedor para mostrar tarjetas manuales en una cuadrícula responsiva.',
            'category' => 'layout',
            'icon' => 'layout-grid',
            'schema_definition' => [
                'fields' => [],
                'config_fields' => [
                    'columns_desktop' => [
                        'type' => 'select',
                        'label' => 'Columnas en Desktop',
                        'options' => ['2', '3', '4'],
                        'default' => '3',
                        'required' => false,
                    ],
                    'variant' => [
                        'type' => 'select',
                        'label' => 'Variante de Diseño',
                        'options' => ['bordered', 'flat', 'minimal'],
                        'default' => 'bordered',
                        'required' => false,
                    ],
                    'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                ],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 1,
            'is_active' => 1,
            'sort_order' => 85,
        ],
        [
            'block_key' => 'card_item',
            'name' => 'Tarjeta',
            'description' => 'Tarjeta individual con imagen, título, descripción y enlace opcional.',
            'category' => 'content',
            'icon' => 'credit-card',
            'schema_definition' => [
                'fields' => [
                    'image' => ['type' => 'file', 'label' => 'Icono o Imagen', 'required' => false, 'accept' => 'image'],
                    'title' => ['type' => 'string', 'label' => 'Título', 'required' => true],
                    'description' => ['type' => 'text', 'label' => 'Descripción', 'required' => false],
                    'link_url' => ['type' => 'url', 'label' => 'URL del Enlace', 'required' => false],
                    'link_label' => ['type' => 'string', 'label' => 'Etiqueta del Enlace', 'required' => false],
                ],
                'config_fields' => [],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 86,
        ],
        [
            'block_key' => 'cards_slider',
            'name' => 'Slider de Tarjetas (Contenedor)',
            'description' => 'Contenedor para mostrar tarjetas editoriales en formato slider o cuadrícula.',
            'category' => 'marketing',
            'icon' => 'message-square',
            'schema_definition' => [
                'fields' => [],
                'config_fields' => [
                    'layout' => [
                        'type' => 'select',
                        'label' => 'Distribución',
                        'options' => ['slider', 'grid'],
                        'default' => 'slider',
                        'required' => false,
                    ],
                    'autoplay' => ['type' => 'boolean', 'label' => 'Reproducción Automática', 'required' => false, 'default' => true],
                    'interval' => ['type' => 'number', 'label' => 'Intervalo (ms)', 'required' => false, 'default' => 5000],
                    'visible_count' => [
                        'type' => 'select',
                        'label' => 'Tarjetas visibles',
                        'options' => ['1', '2', '3'],
                        'default' => '1',
                        'required' => false,
                    ],
                    'card_variant' => [
                        'type' => 'select',
                        'label' => 'Variante de tarjeta',
                        'options' => ['editorial', 'testimonial', 'media'],
                        'default' => 'editorial',
                        'required' => false,
                    ],
                    'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                ],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 1,
            'is_active' => 1,
            'sort_order' => 90,
        ],
        [
            'block_key' => 'slide_card',
            'name' => 'Tarjeta de Slider',
            'description' => 'Tarjeta individual para carrusel o grilla con texto, metadata, imagen, enlace y calificación opcional.',
            'category' => 'marketing',
            'icon' => 'user-check',
            'schema_definition' => [
                'fields' => [
                    'eyebrow' => ['type' => 'string', 'label' => 'Etiqueta superior', 'required' => false],
                    'title' => ['type' => 'string', 'label' => 'Título', 'required' => false],
                    'body' => ['type' => 'text', 'label' => 'Texto', 'required' => false],
                    'meta_title' => ['type' => 'string', 'label' => 'Autor / Nombre / Fuente', 'required' => false],
                    'meta_description' => ['type' => 'string', 'label' => 'Rol / Metadata', 'required' => false],
                    'image' => ['type' => 'file', 'label' => 'Imagen', 'required' => false, 'accept' => 'image'],
                    'rating' => [
                        'type' => 'select',
                        'label' => 'Calificación (Estrellas)',
                        'options' => ['0', '1', '2', '3', '4', '5'],
                        'default' => '0',
                        'required' => false,
                    ],
                    'link_url' => ['type' => 'url', 'label' => 'URL del Enlace', 'required' => false],
                    'link_label' => ['type' => 'string', 'label' => 'Etiqueta del Enlace', 'required' => false],
                ],
                'config_fields' => [],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 91,
        ],
        [
            'block_key' => 'asset_showcase',
            'name' => 'Vitrina de Activos (Contenedor)',
            'description' => 'Contenedor para logos, marcas, auspiciadores, certificaciones o recursos visuales en carrusel o grilla.',
            'category' => 'marketing',
            'icon' => 'images',
            'schema_definition' => [
                'fields' => [],
                'config_fields' => [
                    'layout' => [
                        'type' => 'select',
                        'label' => 'Distribución',
                        'options' => ['marquee', 'grid'],
                        'default' => 'marquee',
                        'required' => false,
                    ],
                    'speed' => [
                        'type' => 'select',
                        'label' => 'Velocidad de Desplazamiento',
                        'options' => ['slow', 'normal', 'fast'],
                        'default' => 'normal',
                        'required' => false,
                    ],
                    'grayscale' => ['type' => 'boolean', 'label' => 'Escala de Grises', 'required' => false, 'default' => true],
                    'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                ],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 1,
            'is_active' => 1,
            'sort_order' => 95,
        ],
        [
            'block_key' => 'asset_item',
            'name' => 'Activo Visual',
            'description' => 'Elemento visual individual dentro de una vitrina de activos.',
            'category' => 'marketing',
            'icon' => 'image',
            'schema_definition' => [
                'fields' => [
                    'logo' => ['type' => 'file', 'label' => 'Imagen del Logo', 'required' => false, 'accept' => 'image'],
                    'name' => ['type' => 'string', 'label' => 'Nombre', 'required' => true],
                    'link_url' => ['type' => 'url', 'label' => 'URL Sitio Web', 'required' => false],
                ],
                'config_fields' => [],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 96,
        ],
        [
            'block_key' => 'map_embed',
            'name' => 'Mapa Embebido',
            'description' => 'Mapa o iframe embebido configurable para ubicación, cobertura, rutas o puntos de atención.',
            'category' => 'contact',
            'icon' => 'map',
            'schema_definition' => [
                'fields' => [
                    'title' => ['type' => 'string', 'label' => 'Título', 'required' => false],
                    'caption' => ['type' => 'text', 'label' => 'Descripción', 'required' => false],
                ],
                'config_fields' => [
                    'embed_url' => ['type' => 'url', 'label' => 'URL Embed', 'required' => true, 'default' => ''],
                    'aspect_ratio' => ['type' => 'select', 'label' => 'Proporción', 'options' => ['16/9', '4/3', '1/1'], 'default' => '16/9', 'required' => false],
                    'height' => ['type' => 'number', 'label' => 'Alto mínimo (px)', 'required' => false, 'default' => 360],
                    'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                ],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 65,
        ],
        [
            'block_key' => 'metrics_grid',
            'name' => 'Grilla de Métricas (Contenedor)',
            'description' => 'Contenedor para mostrar métricas, cifras o logros numéricos.',
            'category' => 'layout',
            'icon' => 'calculator',
            'schema_definition' => [
                'fields' => [],
                'config_fields' => [
                    'variant' => [
                        'type' => 'select',
                        'label' => 'Variante de Color',
                        'options' => ['light', 'dark', 'primary'],
                        'default' => 'light',
                        'required' => false,
                    ],
                    'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                ],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 1,
            'is_active' => 1,
            'sort_order' => 100,
        ],
        [
            'block_key' => 'metric_item',
            'name' => 'Métrica',
            'description' => 'Métrica individual con número, etiqueta e ícono opcional.',
            'category' => 'content',
            'icon' => 'hash',
            'schema_definition' => [
                'fields' => [
                    'prefix' => ['type' => 'string', 'label' => 'Prefijo', 'required' => false],
                    'number' => ['type' => 'string', 'label' => 'Número / Valor', 'required' => true],
                    'suffix' => ['type' => 'string', 'label' => 'Sufijo', 'required' => false],
                    'label' => ['type' => 'string', 'label' => 'Etiqueta', 'required' => true],
                    'description' => ['type' => 'text', 'label' => 'Descripción', 'required' => false],
                    'source_label' => ['type' => 'string', 'label' => 'Fuente', 'required' => false],
                    'source_url' => ['type' => 'url', 'label' => 'URL de Fuente', 'required' => false],
                    'icon' => ['type' => 'string', 'label' => 'Nombre del Icono (Lucide)', 'required' => false],
                ],
                'config_fields' => [],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 101,
        ],
        [
            'block_key' => 'video_player',
            'name' => 'Reproductor de Video',
            'description' => 'Bloque para embeber videos de YouTube, Vimeo o archivos directos con imagen de portada.',
            'category' => 'media',
            'icon' => 'play',
            'schema_definition' => [
                'fields' => [
                    'video_url' => ['type' => 'url', 'label' => 'URL del Video', 'required' => true],
                    'poster' => ['type' => 'file', 'label' => 'Imagen de Portada', 'required' => false, 'accept' => 'image'],
                    'heading' => ['type' => 'string', 'label' => 'Título', 'required' => false],
                ],
                'config_fields' => [
                    'autoplay' => ['type' => 'boolean', 'label' => 'Reproducción Automática', 'required' => false, 'default' => false],
                    'mute' => ['type' => 'boolean', 'label' => 'Silenciado', 'required' => false, 'default' => false],
                    'loop' => ['type' => 'boolean', 'label' => 'Bucle continuo', 'required' => false, 'default' => false],
                    'aspect_ratio' => [
                        'type' => 'select',
                        'label' => 'Relación de Aspecto',
                        'options' => ['16/9', '4/3', 'auto'],
                        'default' => '16/9',
                        'required' => false,
                    ],
                    'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                ],
            ],
            'supports_pages' => 1,
            'supports_entries' => 0,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 110,
        ],
    ];

    public function up(): void
    {
        foreach ($this->blocks as $block) {
            $block['schema_definition'] = json_encode($block['schema_definition'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->db->table('cms_content_blocks')->insert($block);
        }
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();
        $keys = array_column($this->blocks, 'block_key');
        $this->db->table('cms_content_blocks')->whereIn('block_key', $keys)->delete();
        $this->db->enableForeignKeyChecks();
    }
}
