<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNewCmsBlockTypes extends Migration
{
    private array $blocks = [
        [
            'block_key' => 'faq_accordion',
            'name' => 'Acordeón FAQ (Contenedor)',
            'description' => 'Contenedor para agrupar preguntas frecuentes con despliegue interactivo.',
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
            'block_key' => 'faq_item',
            'name' => 'Pregunta FAQ',
            'description' => 'Pregunta y respuesta individual dentro de un Acordeón FAQ.',
            'category' => 'content',
            'icon' => 'help-circle',
            'schema_definition' => [
                'fields' => [
                    'question' => ['type' => 'string', 'label' => 'Pregunta', 'required' => true],
                    'answer' => ['type' => 'richtext', 'label' => 'Respuesta', 'required' => true],
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
            'block_key' => 'features_grid',
            'name' => 'Grilla de Características (Contenedor)',
            'description' => 'Contenedor para mostrar servicios o características en una cuadrícula responsiva.',
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
            'block_key' => 'feature_card',
            'name' => 'Tarjeta de Característica',
            'description' => 'Tarjeta individual de servicio, beneficio o característica con imagen, descripción y enlace.',
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
            'block_key' => 'testimonials_slider',
            'name' => 'Testimonios (Contenedor)',
            'description' => 'Contenedor para mostrar opiniones o testimonios en formato slider o cuadrícula.',
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
            'block_key' => 'testimonial_card',
            'name' => 'Tarjeta de Testimonio',
            'description' => 'Tarjeta de testimonio individual con cita, autor, rol, foto y calificación.',
            'category' => 'marketing',
            'icon' => 'user-check',
            'schema_definition' => [
                'fields' => [
                    'quote' => ['type' => 'text', 'label' => 'Testimonio / Cita', 'required' => true],
                    'author' => ['type' => 'string', 'label' => 'Autor', 'required' => true],
                    'role' => ['type' => 'string', 'label' => 'Organización o Rol', 'required' => false],
                    'avatar' => ['type' => 'file', 'label' => 'Foto del Autor', 'required' => false, 'accept' => 'image'],
                    'rating' => [
                        'type' => 'select',
                        'label' => 'Calificación (Estrellas)',
                        'options' => ['1', '2', '3', '4', '5'],
                        'default' => '5',
                        'required' => false,
                    ],
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
            'block_key' => 'logo_showcase',
            'name' => 'Vitrina de Logos (Contenedor)',
            'description' => 'Contenedor para marcas o auspiciadores en carrusel infinito o grilla.',
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
            'block_key' => 'logo_item',
            'name' => 'Logo de Auspiciador',
            'description' => 'Logo individual dentro de la vitrina de auspiciadores.',
            'category' => 'marketing',
            'icon' => 'image',
            'schema_definition' => [
                'fields' => [
                    'logo' => ['type' => 'file', 'label' => 'Imagen del Logo', 'required' => true, 'accept' => 'image'],
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
            'block_key' => 'stats_section',
            'name' => 'Sección de Estadísticas (Contenedor)',
            'description' => 'Contenedor para mostrar cifras o logros numéricos con animación.',
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
            'block_key' => 'stat_item',
            'name' => 'Cifra de Estadística',
            'description' => 'Logro o estadística individual con número animado e ícono.',
            'category' => 'content',
            'icon' => 'hash',
            'schema_definition' => [
                'fields' => [
                    'number' => ['type' => 'string', 'label' => 'Número / Valor', 'required' => true],
                    'label' => ['type' => 'string', 'label' => 'Etiqueta', 'required' => true],
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
