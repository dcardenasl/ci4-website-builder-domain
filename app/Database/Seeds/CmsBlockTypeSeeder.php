<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CmsBlockTypeSeeder extends Seeder
{
    public function run(): void
    {
        $blocks = [
            // ── hero_slider ─────────────────────────────────────────────────────
            // Contenedor de carrusel. Los slides son bloques hijos de tipo slide_banner
            // vinculados por parent_instance_id. Esto permite agregar/reordenar slides
            // individualmente desde el admin sin límite de cantidad.
            [
                'block_key'         => 'hero_slider',
                'name'              => 'Carrusel Hero',
                'description'       => 'Contenedor de carrusel a ancho completo con posiciones configurables para texto y controles. Agrega bloques de tipo "Diapositiva" como hijos para definir los slides.',
                'category'          => 'marketing',
                'icon'              => 'gallery-horizontal',
                'schema_definition' => json_encode([
                    'fields'        => [],
                    'config_fields' => [
                        'autoplay'        => ['type' => 'boolean', 'label' => 'Reproducción automática', 'required' => false, 'default' => true],
                        'interval'        => ['type' => 'number',  'label' => 'Intervalo (ms)',           'required' => false, 'default' => 6000],
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
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 1,
                'is_active'        => 1,
                'sort_order'       => 1,
            ],

            // ── slide_banner ─────────────────────────────────────────────────────
            // Bloque hijo para hero_slider. No se usa directamente en páginas;
            // se crea como hijo de un bloque hero_slider (parent_instance_id).
            [
                'block_key'         => 'slide_banner',
                'name'              => 'Diapositiva',
                'description'       => 'Slide individual para el carrusel hero. Contiene imagen, título, subtítulo y botón CTA. Debe usarse como hijo de un bloque Carrusel Hero.',
                'category'          => 'marketing',
                'icon'              => 'image',
                'schema_definition' => json_encode([
                    'fields' => [
                        'image'     => ['type' => 'file',   'label' => 'Imagen',           'required' => true,  'accept' => 'image'],
                        'heading'   => ['type' => 'string', 'label' => 'Título',           'required' => true],
                        'subtitle'  => ['type' => 'string', 'label' => 'Subtítulo',        'required' => false],
                        'cta_label' => ['type' => 'string', 'label' => 'Texto del botón',  'required' => false],
                        'cta_url'   => ['type' => 'url',    'label' => 'URL del botón',    'required' => false],
                    ],
                    'config_fields' => [],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 2,
            ],

            // ── hero_banner ──────────────────────────────────────────────────────
            // La imagen se selecciona del file manager. Convención file type:
            //   block_data["image_file_id"] + block_data["image_url"]
            [
                'block_key'         => 'hero_banner',
                'name'              => 'Hero Banner',
                'description'       => 'Banner a ancho completo con imagen de fondo, título, subtítulo y botón CTA.',
                'category'          => 'marketing',
                'icon'              => 'layout',
                'schema_definition' => json_encode([
                    'fields' => [
                        'image'      => ['type' => 'file',   'label' => 'Imagen de fondo',    'required' => true,  'accept' => 'image'],
                        'alt'        => ['type' => 'string', 'label' => 'Texto Alt (fallback)', 'required' => false],
                        'heading'    => ['type' => 'string', 'label' => 'Título Principal',   'required' => true],
                        'subheading' => ['type' => 'string', 'label' => 'Subtítulo',          'required' => false],
                        'cta_label'  => ['type' => 'string', 'label' => 'Texto del Botón CTA', 'required' => false],
                        'cta_url'    => ['type' => 'url',    'label' => 'URL del Botón CTA',  'required' => false],
                    ],
                    'config_fields' => [
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 1,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 5,
            ],

            // ── rich_text ────────────────────────────────────────────────────────
            [
                'block_key'         => 'rich_text',
                'name'              => 'Texto Enriquecido',
                'description'       => 'Bloque de texto con formato HTML completo.',
                'category'          => 'content',
                'icon'              => 'align-left',
                'schema_definition' => json_encode([
                    'fields' => [
                        'content' => ['type' => 'richtext', 'label' => 'Contenido', 'required' => true],
                    ],
                    'config_fields' => [
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 1,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 10,
            ],

            // ── image ────────────────────────────────────────────────────────────
            // Mantiene el esquema legacy (file_id + url separados) por compatibilidad.
            [
                'block_key'         => 'image',
                'name'              => 'Imagen',
                'description'       => 'Imagen individual con pie de foto opcional.',
                'category'          => 'media',
                'icon'              => 'image',
                'schema_definition' => json_encode([
                    'fields' => [
                        'image'   => ['type' => 'file',   'label' => 'Imagen',          'required' => false, 'accept' => 'image'],
                        'alt'     => ['type' => 'string', 'label' => 'Texto Alternativo', 'required' => false],
                        'caption' => ['type' => 'string', 'label' => 'Pie de Foto',      'required' => false],
                    ],
                    'config_fields' => [
                        'css_class'    => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                        'aspect_ratio' => [
                            'type'     => 'select',
                            'label'    => 'Proporción',
                            'options'  => ['auto', '16/9', '4/3', '1/1'],
                            'default'  => 'auto',
                            'required' => false,
                        ],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 1,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 20,
            ],

            // ── events_grid ──────────────────────────────────────────────────────
            [
                'block_key'         => 'events_grid',
                'name'              => 'Grilla de Eventos',
                'description'       => 'Sección de cartelera con tarjetas de eventos. Carga entradas desde una colección del CMS.',
                'category'          => 'content',
                'icon'              => 'calendar-days',
                'schema_definition' => json_encode([
                    'fields' => [
                        'section_title'    => ['type' => 'string', 'label' => 'Título de sección',                    'required' => false],
                        'section_subtitle' => ['type' => 'string', 'label' => 'Subtítulo de sección',                 'required' => false],
                        'view_all_label'   => ['type' => 'string', 'label' => 'Texto del enlace "Ver todos"',         'required' => false],
                        'view_all_url'     => ['type' => 'url',    'label' => 'URL del enlace "Ver todos"',           'required' => false],
                        'empty_message'    => ['type' => 'string', 'label' => 'Mensaje cuando no hay contenido',      'required' => false],
                    ],
                    'config_fields' => [
                        'collection_key' => ['type' => 'string', 'label' => 'Clave de Colección (CMS)', 'required' => true,  'default' => 'cartelera'],
                        'items_limit'    => ['type' => 'number', 'label' => 'Máx. elementos',           'required' => false, 'default' => 6],
                        'css_class'      => ['type' => 'string', 'label' => 'Clase CSS',               'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 15,
            ],

            // ── news_grid ────────────────────────────────────────────────────────
            [
                'block_key'         => 'news_grid',
                'name'              => 'Grilla de Noticias',
                'description'       => 'Sección de noticias con tarjetas en grilla de 3 columnas. Carga entradas desde una colección del CMS.',
                'category'          => 'content',
                'icon'              => 'newspaper',
                'schema_definition' => json_encode([
                    'fields' => [
                        'section_title'    => ['type' => 'string', 'label' => 'Título de sección',               'required' => false],
                        'section_subtitle' => ['type' => 'string', 'label' => 'Subtítulo de sección',            'required' => false],
                        'view_all_label'   => ['type' => 'string', 'label' => 'Texto del enlace "Ver todas"',    'required' => false],
                        'view_all_url'     => ['type' => 'url',    'label' => 'URL del enlace "Ver todas"',      'required' => false],
                        'empty_message'    => ['type' => 'string', 'label' => 'Mensaje cuando no hay noticias',  'required' => false],
                    ],
                    'config_fields' => [
                        'collection_key' => ['type' => 'string', 'label' => 'Clave de Colección (CMS)', 'required' => true,  'default' => 'noticias'],
                        'items_limit'    => ['type' => 'number', 'label' => 'Máx. elementos',           'required' => false, 'default' => 3],
                        'css_class'      => ['type' => 'string', 'label' => 'Clase CSS',               'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 25,
            ],

            // ── cta ──────────────────────────────────────────────────────────────
            [
                'block_key'         => 'cta',
                'name'              => 'Llamada a la Acción (CTA)',
                'description'       => 'Sección destacada con título, descripción y botón de acción.',
                'category'          => 'marketing',
                'icon'              => 'mouse-pointer',
                'schema_definition' => json_encode([
                    'fields' => [
                        'heading' => ['type' => 'string', 'label' => 'Título',          'required' => true],
                        'text'    => ['type' => 'text',   'label' => 'Descripción',     'required' => false],
                        'label'   => ['type' => 'string', 'label' => 'Texto del Botón', 'required' => true],
                        'url'     => ['type' => 'url',    'label' => 'URL del Botón',   'required' => true],
                    ],
                    'config_fields' => [
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                        'variant'   => [
                            'type'     => 'select',
                            'label'    => 'Variante de Color',
                            'options'  => ['blue', 'dark', 'light'],
                            'default'  => 'blue',
                            'required' => false,
                        ],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 1,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 30,
            ],

            // ── container ────────────────────────────────────────────────────────
            [
                'block_key'         => 'container',
                'name'              => 'Contenedor',
                'description'       => 'Agrupa y organiza bloques hijo. Útil para layouts en columnas o secciones con fondo.',
                'category'          => 'layout',
                'icon'              => 'layout-template',
                'schema_definition' => json_encode([
                    'fields'        => [],
                    'config_fields' => [
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => 'container mx-auto px-4'],
                        'layout'    => [
                            'type'     => 'select',
                            'label'    => 'Distribución',
                            'options'  => ['block', 'grid-2', 'grid-3', 'flex-row'],
                            'default'  => 'block',
                            'required' => false,
                        ],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 1,
                'is_active'        => 1,
                'sort_order'       => 100,
            ],

            // ── page_header ──────────────────────────────────────────────────────
            [
                'block_key'         => 'page_header',
                'name'              => 'Encabezado de Página',
                'description'       => 'Encabezado de sección con título principal y migas de pan (breadcrumb).',
                'category'          => 'navigation',
                'icon'              => 'heading',
                'schema_definition' => json_encode([
                    'fields' => [
                        'heading'          => ['type' => 'string', 'label' => 'Título',              'required' => true],
                        'subheading'       => ['type' => 'string', 'label' => 'Subtítulo',           'required' => false],
                        'breadcrumb_label' => ['type' => 'string', 'label' => 'Etiqueta breadcrumb', 'required' => false],
                        'breadcrumb_url'   => ['type' => 'url',    'label' => 'URL breadcrumb',      'required' => false],
                    ],
                    'config_fields' => [
                        'bg_color'  => ['type' => 'string', 'label' => 'Color de fondo (Tailwind)', 'required' => false, 'default' => 'bg-gray-100'],
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS',                 'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 1,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 40,
            ],

            // ── contact_form ─────────────────────────────────────────────────────
            // Todos los labels y textos son configurables desde el admin.
            [
                'block_key'         => 'contact_form',
                'name'              => 'Formulario de Contacto',
                'description'       => 'Formulario con campos para empresa, nombre, email, teléfono y mensaje. Todos los labels son configurables.',
                'category'          => 'interactive',
                'icon'              => 'mail',
                'schema_definition' => json_encode([
                    'fields' => [
                        'heading'          => ['type' => 'string', 'label' => 'Título del formulario',       'required' => false],
                        'description'      => ['type' => 'text',   'label' => 'Descripción / intro',         'required' => false],
                        'label_company'    => ['type' => 'string', 'label' => 'Label campo Empresa',         'required' => false],
                        'label_name'       => ['type' => 'string', 'label' => 'Label campo Nombre',          'required' => false],
                        'label_email'      => ['type' => 'string', 'label' => 'Label campo Email',           'required' => false],
                        'label_phone'      => ['type' => 'string', 'label' => 'Label campo Teléfono',        'required' => false],
                        'phone_prefix'     => ['type' => 'string', 'label' => 'Prefijo teléfono (ej. +56)',  'required' => false],
                        'label_message'    => ['type' => 'string', 'label' => 'Label campo Mensaje',         'required' => false],
                        'info_email_label' => ['type' => 'string', 'label' => 'Info box — Label Correo',     'required' => false],
                        'info_email_desc'  => ['type' => 'string', 'label' => 'Info box — Descripción Correo', 'required' => false],
                        'info_phone_label' => ['type' => 'string', 'label' => 'Info box — Label Teléfono',   'required' => false],
                        'info_phone_desc'  => ['type' => 'string', 'label' => 'Info box — Descripción Teléfono', 'required' => false],
                        'submit_label'     => ['type' => 'string', 'label' => 'Texto del botón enviar',      'required' => false],
                        'success_message'  => ['type' => 'string', 'label' => 'Mensaje de éxito',            'required' => false],
                    ],
                    'config_fields' => [
                        'email_to'        => ['type' => 'string',  'label' => 'Email de destino',           'required' => false, 'default' => ''],
                        'show_company'    => ['type' => 'boolean', 'label' => 'Mostrar campo Empresa',      'required' => false, 'default' => true],
                        'show_info_boxes' => ['type' => 'boolean', 'label' => 'Mostrar info boxes laterales', 'required' => false, 'default' => true],
                        'css_class'       => ['type' => 'string',  'label' => 'Clase CSS',                  'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 50,
            ],

            // ── location_info ────────────────────────────────────────────────────
            [
                'block_key'         => 'location_info',
                'name'              => 'Información de Ubicación',
                'description'       => 'Dirección, teléfono, horarios y mapa embebido de Google Maps.',
                'category'          => 'contact',
                'icon'              => 'map-pin',
                'schema_definition' => json_encode([
                    'fields' => [
                        'section_title'       => ['type' => 'string', 'label' => 'Título de sección',           'required' => false],
                        'section_description' => ['type' => 'text',   'label' => 'Descripción de sección',      'required' => false],
                        'address_label'       => ['type' => 'string', 'label' => 'Etiqueta Dirección',          'required' => false],
                        'address'             => ['type' => 'string', 'label' => 'Dirección',                   'required' => false],
                        'phone_label'         => ['type' => 'string', 'label' => 'Etiqueta Teléfono',           'required' => false],
                        'phone'               => ['type' => 'string', 'label' => 'Teléfono',                    'required' => false],
                        'hours_label'         => ['type' => 'string', 'label' => 'Etiqueta Horarios',           'required' => false],
                        'hours'               => ['type' => 'text',   'label' => 'Horarios',                    'required' => false],
                    ],
                    'config_fields' => [
                        'map_embed_url' => ['type' => 'url',    'label' => 'URL Embed Google Maps', 'required' => false, 'default' => ''],
                        'css_class'     => ['type' => 'string', 'label' => 'Clase CSS',             'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 60,
            ],

            // ── social_links ─────────────────────────────────────────────────────
            [
                'block_key'         => 'social_links',
                'name'              => 'Redes Sociales',
                'description'       => 'Bloque con enlaces y handles de redes sociales: Facebook, Instagram, Twitter/X y YouTube.',
                'category'          => 'social',
                'icon'              => 'share-2',
                'schema_definition' => json_encode([
                    'fields' => [
                        'heading' => ['type' => 'string', 'label' => 'Título de sección', 'required' => false],
                    ],
                    'config_fields' => [
                        'facebook_url'     => ['type' => 'url',    'label' => 'URL Facebook',     'required' => false, 'default' => ''],
                        'facebook_handle'  => ['type' => 'string', 'label' => 'Handle Facebook',  'required' => false, 'default' => ''],
                        'instagram_url'    => ['type' => 'url',    'label' => 'URL Instagram',    'required' => false, 'default' => ''],
                        'instagram_handle' => ['type' => 'string', 'label' => 'Handle Instagram', 'required' => false, 'default' => ''],
                        'twitter_url'      => ['type' => 'url',    'label' => 'URL Twitter/X',    'required' => false, 'default' => ''],
                        'youtube_url'      => ['type' => 'url',    'label' => 'URL YouTube',      'required' => false, 'default' => ''],
                        'css_class'        => ['type' => 'string', 'label' => 'Clase CSS',        'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 70,
            ],
        ];

        foreach ($blocks as $block) {
            $existing = $this->db->table('cms_content_blocks')
                ->where('block_key', $block['block_key'])
                ->get()
                ->getRow();

            if ($existing === null) {
                $this->db->table('cms_content_blocks')->insert($block);
            } else {
                $this->db->table('cms_content_blocks')
                    ->where('id', $existing->id)
                    ->update($block);
            }
        }
    }

}
