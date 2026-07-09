<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Installs starter block type examples for the default website.
 *
 * Block types are editable database configuration composed from native field
 * primitives. The CMS wizard must not require this seeder to be installed.
 */
class CmsBlockTypeSeeder extends Seeder
{
    use IdempotentSeederSupport;

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
                    'allowed_children' => ['slide_banner'],
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
                    'config_fields' => [
                        'text_color' => ['type' => 'color', 'label' => 'Color del texto', 'required' => false, 'default' => '#ffffff'],
                        'overlay_color' => ['type' => 'color', 'label' => 'Filtro de fondo', 'required' => false, 'default' => 'rgba(15, 23, 42, 0.4)'],
                    ],
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
                        'text_color' => ['type' => 'color', 'label' => 'Color del texto', 'required' => false, 'default' => '#ffffff'],
                        'overlay_color' => ['type' => 'color', 'label' => 'Filtro de fondo', 'required' => false, 'default' => 'rgba(15, 23, 42, 0.4)'],
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
            // Mantiene el esquema actual (file_id + url separados) por compatibilidad.
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

            // ── collection_grid ──────────────────────────────────────────────────
            [
                'block_key'         => 'collection_grid',
                'name'              => 'Grilla de Colección',
                'description'       => 'Lista entradas publicadas desde cualquier colección del CMS con límite, orden y variante visual configurables.',
                'category'          => 'content',
                'icon'              => 'layout-grid',
                'schema_definition' => json_encode([
                    'fields' => [
                        'section_title'    => ['type' => 'string', 'label' => 'Título de sección',               'required' => false],
                        'section_subtitle' => ['type' => 'string', 'label' => 'Subtítulo de sección',            'required' => false],
                        'view_all_label'   => ['type' => 'string', 'label' => 'Texto del enlace "Ver todos"',    'required' => false],
                        'view_all_url'     => ['type' => 'url',    'label' => 'URL del enlace "Ver todos"',      'required' => false],
                        'empty_message'    => ['type' => 'string', 'label' => 'Mensaje cuando no hay contenido', 'required' => false],
                    ],
                    'config_fields' => [
                        'collection_key'  => ['type' => 'string', 'label' => 'Clave de Colección (CMS)', 'required' => true,  'default' => 'noticias'],
                        'items_limit'     => ['type' => 'number', 'label' => 'Máx. elementos',           'required' => false, 'default' => 3],
                        'order_by'        => ['type' => 'select', 'label' => 'Ordenar por',              'required' => false, 'options' => ['published_at', 'sort_order', 'created_at', 'title'], 'default' => 'published_at'],
                        'order_direction' => ['type' => 'select', 'label' => 'Dirección',                'required' => false, 'options' => ['asc', 'desc'], 'default' => 'desc'],
                        'layout_variant'  => ['type' => 'select', 'label' => 'Variante visual',          'required' => false, 'options' => ['cards', 'compact', 'portfolio'], 'default' => 'cards'],
                        'css_class'       => ['type' => 'string', 'label' => 'Clase CSS',                'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 15,
            ],

            // ── collection_listing ──────────────────────────────────────────────
            [
                'block_key'         => 'collection_listing',
                'name'              => 'Listado de Colección',
                'description'       => 'Listado público completo de una colección, con filtros, orden, búsqueda y paginación.',
                'category'          => 'content',
                'icon'              => 'list-tree',
                'schema_definition' => json_encode([
                    'fields' => [
                        'intro_title'      => ['type' => 'string', 'label' => 'Título introductorio',              'required' => false],
                        'intro_text'       => ['type' => 'richtext', 'label' => 'Texto introductorio',            'required' => false],
                        'empty_message'    => ['type' => 'string', 'label' => 'Mensaje cuando no hay contenido', 'required' => false],
                        'css_class'        => ['type' => 'string', 'label' => 'Clase CSS',                       'required' => false, 'default' => ''],
                    ],
                    'config_fields' => [
                        'collection_id'    => ['type' => 'select', 'label' => 'Colección', 'required' => true, 'options' => [], 'default' => ''],
                        'per_page'         => ['type' => 'number', 'label' => 'Elementos por página',      'required' => false, 'default' => 12],
                        'order_by'         => ['type' => 'select', 'label' => 'Ordenar por',               'required' => false, 'options' => ['published_at', 'sort_order', 'created_at', 'title'], 'default' => 'published_at'],
                        'order_direction'  => ['type' => 'select', 'label' => 'Dirección',                 'required' => false, 'options' => ['asc', 'desc'], 'default' => 'desc'],
                        'layout_variant'   => ['type' => 'select', 'label' => 'Variante visual',           'required' => false, 'options' => ['cards', 'compact', 'portfolio'], 'default' => 'cards'],
                        'show_search'      => ['type' => 'boolean', 'label' => 'Mostrar búsqueda',          'required' => false, 'default' => true],
                        'show_categories'  => ['type' => 'boolean', 'label' => 'Mostrar categorías',       'required' => false, 'default' => true],
                        'show_tags'        => ['type' => 'boolean', 'label' => 'Mostrar etiquetas',         'required' => false, 'default' => false],
                        'css_class'        => ['type' => 'string', 'label' => 'Clase CSS',                 'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 16,
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
                    'allowed_children' => ['rich_text', 'image', 'cta', 'video_player', 'form_embed', 'contact_info', 'map_embed', 'social_links', 'hero_banner', 'accordion', 'cards_grid', 'cards_slider', 'asset_showcase', 'metrics_grid', 'tabs', 'alert', 'gallery', 'collection_grid', 'collection_listing'],
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
                        'breadcrumb_url'   => ['type' => 'string', 'label' => 'URL breadcrumb',      'required' => false],
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

            // ── form_embed ─────────────────────────────────────────────────────
            // Todos los labels y textos son configurables desde el admin.
            [
                'block_key'         => 'form_embed',
                'name'              => 'Formulario Embebido',
                'description'       => 'Renderiza cualquier formulario activo del CMS mediante su clave.',
                'category'          => 'interactive',
                'icon'              => 'mail',
                'schema_definition' => json_encode([
                    'fields'        => [],
                    'config_fields' => [
                        'form_key'  => ['type' => 'string', 'label' => 'Formulario', 'required' => true, 'default' => 'contact'],
                        'css_class' => ['type' => 'string', 'label' => 'CSS Class', 'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 50,
            ],

            // ── contact_info ────────────────────────────────────────────────────
            [
                'block_key'         => 'contact_info',
                'name'              => 'Información de Contacto',
                'description'       => 'Datos de contacto estructurados: dirección, teléfono, correo, horarios y notas de atención.',
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
                        'email_label'         => ['type' => 'string', 'label' => 'Etiqueta Email',              'required' => false],
                        'email'               => ['type' => 'string', 'label' => 'Email',                       'required' => false],
                        'hours_label'         => ['type' => 'string', 'label' => 'Etiqueta Horarios',           'required' => false],
                        'hours'               => ['type' => 'text',   'label' => 'Horarios',                    'required' => false],
                    ],
                    'config_fields' => [
                        'layout'    => ['type' => 'select', 'label' => 'Distribución', 'options' => ['stacked', 'two_columns'], 'default' => 'stacked', 'required' => false],
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS',    'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 60,
            ],

            // ── map_embed ───────────────────────────────────────────────────────
            [
                'block_key'         => 'map_embed',
                'name'              => 'Mapa Embebido',
                'description'       => 'Mapa o iframe embebido configurable para ubicación, cobertura, rutas o puntos de atención.',
                'category'          => 'contact',
                'icon'              => 'map',
                'schema_definition' => json_encode([
                    'fields' => [
                        'title'   => ['type' => 'string', 'label' => 'Título',      'required' => false],
                        'caption' => ['type' => 'text',   'label' => 'Descripción', 'required' => false],
                    ],
                    'config_fields' => [
                        'embed_url'    => ['type' => 'url',    'label' => 'URL Embed',       'required' => true,  'default' => ''],
                        'aspect_ratio' => ['type' => 'select', 'label' => 'Proporción',      'options' => ['16/9', '4/3', '1/1'], 'default' => '16/9', 'required' => false],
                        'height'       => ['type' => 'number', 'label' => 'Alto mínimo (px)', 'required' => false, 'default' => 360],
                        'css_class'    => ['type' => 'string', 'label' => 'Clase CSS',       'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 65,
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

            // ── accordion ─────────────────────────────────────────────────────
            [
                'block_key'         => 'accordion',
                'name'              => 'Acordeón (Contenedor)',
                'description'       => 'Contenedor para agrupar elementos desplegables como preguntas, requisitos, programa o detalles.',
                'category'          => 'content',
                'icon'              => 'list',
                'schema_definition' => json_encode([
                    'fields' => [],
                    'config_fields' => [
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                    'allowed_children' => ['accordion_item'],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 1,
                'is_active'        => 1,
                'sort_order'       => 80,
            ],

            // ── accordion_item ──────────────────────────────────────────────────────────
            [
                'block_key'         => 'accordion_item',
                'name'              => 'Elemento de Acordeón',
                'description'       => 'Elemento desplegable individual dentro de un acordeón.',
                'category'          => 'content',
                'icon'              => 'help-circle',
                'schema_definition' => json_encode([
                    'fields' => [
                        'title'    => ['type' => 'string', 'label' => 'Título', 'required' => true],
                        'content'  => ['type' => 'richtext', 'label' => 'Contenido', 'required' => true],
                    ],
                    'config_fields' => [
                        'is_open' => ['type' => 'boolean', 'label' => 'Abierto por defecto', 'required' => false, 'default' => false],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 81,
            ],

            // ── cards_grid ─────────────────────────────────────────────────────
            [
                'block_key'         => 'cards_grid',
                'name'              => 'Grilla de Tarjetas (Contenedor)',
                'description'       => 'Contenedor para mostrar tarjetas manuales en una cuadrícula responsiva.',
                'category'          => 'layout',
                'icon'              => 'layout-grid',
                'schema_definition' => json_encode([
                    'fields' => [],
                    'config_fields' => [
                        'columns_desktop' => [
                            'type'     => 'select',
                            'label'    => 'Columnas en Desktop',
                            'options'  => ['2', '3', '4'],
                            'default'  => '3',
                            'required' => false,
                        ],
                        'variant' => [
                            'type'     => 'select',
                            'label'    => 'Variante de Diseño',
                            'options'  => ['bordered', 'flat', 'minimal'],
                            'default'  => 'bordered',
                            'required' => false,
                        ],
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                    'allowed_children' => ['card_item'],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 1,
                'is_active'        => 1,
                'sort_order'       => 85,
            ],

            // ── card_item ──────────────────────────────────────────────────────
            [
                'block_key'         => 'card_item',
                'name'              => 'Tarjeta',
                'description'       => 'Tarjeta individual con imagen, título, descripción y enlace opcional.',
                'category'          => 'content',
                'icon'              => 'credit-card',
                'schema_definition' => json_encode([
                    'fields' => [
                        'image'       => ['type' => 'file',   'label' => 'Icono o Imagen',      'required' => false, 'accept' => 'image'],
                        'title'       => ['type' => 'string', 'label' => 'Título',              'required' => true],
                        'description' => ['type' => 'text',   'label' => 'Descripción',         'required' => false],
                        'link_url'    => ['type' => 'url',    'label' => 'URL del Enlace',      'required' => false],
                        'link_label'  => ['type' => 'string', 'label' => 'Etiqueta del Enlace', 'required' => false],
                    ],
                    'config_fields' => [],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 86,
            ],

            // ── cards_slider ──────────────────────────────────────────────
            [
                'block_key'         => 'cards_slider',
                'name'              => 'Slider de Tarjetas (Contenedor)',
                'description'       => 'Contenedor para mostrar tarjetas editoriales en formato slider o cuadrícula.',
                'category'          => 'marketing',
                'icon'              => 'message-square',
                'schema_definition' => json_encode([
                    'fields' => [],
                    'config_fields' => [
                        'layout' => [
                            'type'     => 'select',
                            'label'    => 'Distribución',
                            'options'  => ['slider', 'grid'],
                            'default'  => 'slider',
                            'required' => false,
                        ],
                        'autoplay'  => ['type' => 'boolean', 'label' => 'Reproducción Automática', 'required' => false, 'default' => true],
                        'interval'  => ['type' => 'number',  'label' => 'Intervalo (ms)',           'required' => false, 'default' => 5000],
                        'visible_count' => [
                            'type'     => 'select',
                            'label'    => 'Tarjetas visibles',
                            'options'  => ['1', '2', '3'],
                            'default'  => '1',
                            'required' => false,
                        ],
                        'card_variant' => [
                            'type'     => 'select',
                            'label'    => 'Variante de tarjeta',
                            'options'  => ['editorial', 'testimonial', 'media'],
                            'default'  => 'editorial',
                            'required' => false,
                        ],
                        'css_class' => ['type' => 'string',  'label' => 'Clase CSS',                 'required' => false, 'default' => ''],
                    ],
                    'allowed_children' => ['slide_card'],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 1,
                'is_active'        => 1,
                'sort_order'       => 90,
            ],

            // ── slide_card ──────────────────────────────────────────────────
            [
                'block_key'         => 'slide_card',
                'name'              => 'Tarjeta de Slider',
                'description'       => 'Tarjeta individual para carrusel o grilla con texto, metadata, imagen, enlace y calificación opcional.',
                'category'          => 'marketing',
                'icon'              => 'user-check',
                'schema_definition' => json_encode([
                    'fields' => [
                        'eyebrow'          => ['type' => 'string', 'label' => 'Etiqueta superior',       'required' => false],
                        'title'            => ['type' => 'string', 'label' => 'Título',                  'required' => false],
                        'body'             => ['type' => 'text',   'label' => 'Texto',                   'required' => false],
                        'meta_title'       => ['type' => 'string', 'label' => 'Autor / Nombre / Fuente', 'required' => false],
                        'meta_description' => ['type' => 'string', 'label' => 'Rol / Metadata',          'required' => false],
                        'image'            => ['type' => 'file',   'label' => 'Imagen',                  'required' => false, 'accept' => 'image'],
                        'rating' => [
                            'type'     => 'select',
                            'label'    => 'Calificación (Estrellas)',
                            'options'  => ['0', '1', '2', '3', '4', '5'],
                            'default'  => '0',
                            'required' => false,
                        ],
                        'link_url'   => ['type' => 'url',    'label' => 'URL del Enlace',      'required' => false],
                        'link_label' => ['type' => 'string', 'label' => 'Etiqueta del Enlace', 'required' => false],
                    ],
                    'config_fields' => [],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 91,
            ],

            // ── asset_showcase ─────────────────────────────────────────────────────
            [
                'block_key'         => 'asset_showcase',
                'name'              => 'Vitrina de Activos (Contenedor)',
                'description'       => 'Contenedor para logos, marcas, auspiciadores, certificaciones o recursos visuales en carrusel o grilla.',
                'category'          => 'marketing',
                'icon'              => 'images',
                'schema_definition' => json_encode([
                    'fields' => [],
                    'config_fields' => [
                        'layout' => [
                            'type'     => 'select',
                            'label'    => 'Distribución',
                            'options'  => ['marquee', 'grid'],
                            'default'  => 'marquee',
                            'required' => false,
                        ],
                        'speed' => [
                            'type'     => 'select',
                            'label'    => 'Velocidad de Desplazamiento',
                            'options'  => ['slow', 'normal', 'fast'],
                            'default'  => 'normal',
                            'required' => false,
                        ],
                        'grayscale' => ['type' => 'boolean', 'label' => 'Escala de Grises', 'required' => false, 'default' => true],
                        'css_class' => ['type' => 'string',  'label' => 'Clase CSS',        'required' => false, 'default' => ''],
                    ],
                    'allowed_children' => ['asset_item'],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 1,
                'is_active'        => 1,
                'sort_order'       => 95,
            ],

            // ── asset_item ─────────────────────────────────────────────────────────
            [
                'block_key'         => 'asset_item',
                'name'              => 'Activo Visual',
                'description'       => 'Elemento visual individual dentro de una vitrina de activos.',
                'category'          => 'marketing',
                'icon'              => 'image',
                'schema_definition' => json_encode([
                    'fields' => [
                        'logo'     => ['type' => 'file',   'label' => 'Imagen del Logo', 'required' => false, 'accept' => 'image'],
                        'name'     => ['type' => 'string', 'label' => 'Nombre',            'required' => true],
                        'link_url' => ['type' => 'url',    'label' => 'URL Sitio Web',     'required' => false],
                    ],
                    'config_fields' => [],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 96,
            ],

            // ── metrics_grid ─────────────────────────────────────────────────────
            [
                'block_key'         => 'metrics_grid',
                'name'              => 'Grilla de Métricas (Contenedor)',
                'description'       => 'Contenedor para mostrar métricas, cifras o logros numéricos.',
                'category'          => 'layout',
                'icon'              => 'calculator',
                'schema_definition' => json_encode([
                    'fields' => [],
                    'config_fields' => [
                        'variant' => [
                            'type'     => 'select',
                            'label'    => 'Variante de Color',
                            'options'  => ['light', 'dark', 'primary'],
                            'default'  => 'light',
                            'required' => false,
                        ],
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                    'allowed_children' => ['metric_item'],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 1,
                'is_active'        => 1,
                'sort_order'       => 100,
            ],

            // ── metric_item ─────────────────────────────────────────────────────────
            [
                'block_key'         => 'metric_item',
                'name'              => 'Métrica',
                'description'       => 'Métrica individual con número, etiqueta e ícono opcional.',
                'category'          => 'content',
                'icon'              => 'hash',
                'schema_definition' => json_encode([
                    'fields' => [
                        'prefix'      => ['type' => 'string', 'label' => 'Prefijo',                    'required' => false],
                        'number'      => ['type' => 'string', 'label' => 'Número / Valor',             'required' => true],
                        'suffix'      => ['type' => 'string', 'label' => 'Sufijo',                     'required' => false],
                        'label'       => ['type' => 'string', 'label' => 'Etiqueta',                   'required' => true],
                        'description' => ['type' => 'text',   'label' => 'Descripción',                'required' => false],
                        'source_label' => ['type' => 'string', 'label' => 'Fuente',                     'required' => false],
                        'source_url'  => ['type' => 'url',    'label' => 'URL de Fuente',              'required' => false],
                        'icon'        => ['type' => 'string', 'label' => 'Nombre del Icono (Lucide)', 'required' => false],
                    ],
                    'config_fields' => [],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 101,
            ],

            // ── video_player ──────────────────────────────────────────────────────
            [
                'block_key'         => 'video_player',
                'name'              => 'Reproductor de Video',
                'description'       => 'Bloque para embeber videos de YouTube, Vimeo o archivos directos con imagen de portada.',
                'category'          => 'media',
                'icon'              => 'play',
                'schema_definition' => json_encode([
                    'fields' => [
                        'video_url' => ['type' => 'url',    'label' => 'URL del Video',     'required' => true],
                        'poster'    => ['type' => 'file',   'label' => 'Imagen de Portada', 'required' => false, 'accept' => 'image'],
                        'heading'   => ['type' => 'string', 'label' => 'Título',            'required' => false],
                    ],
                    'config_fields' => [
                        'autoplay' => ['type' => 'boolean', 'label' => 'Reproducción Automática', 'required' => false, 'default' => false],
                        'mute'     => ['type' => 'boolean', 'label' => 'Silenciado',             'required' => false, 'default' => false],
                        'loop'     => ['type' => 'boolean', 'label' => 'Bucle continuo',          'required' => false, 'default' => false],
                        'aspect_ratio' => [
                            'type'     => 'select',
                            'label'    => 'Relación de Aspecto',
                            'options'  => ['16/9', '4/3', 'auto'],
                            'default'  => '16/9',
                            'required' => false,
                        ],
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 110,
            ],
            // ── tabs ─────────────────────────────────────────────────────────────
            [
                'block_key'         => 'tabs',
                'name'              => 'Pestañas (Contenedor)',
                'description'       => 'Contenedor para pestañas de contenido interactivas.',
                'category'          => 'layout',
                'icon'              => 'folder',
                'schema_definition' => json_encode([
                    'fields' => [],
                    'config_fields' => [
                        'layout' => [
                            'type'     => 'select',
                            'label'    => 'Distribución',
                            'options'  => ['horizontal', 'vertical'],
                            'default'  => 'horizontal',
                            'required' => false,
                        ],
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                    'allowed_children' => ['tab_item'],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 1,
                'is_active'        => 1,
                'sort_order'       => 120,
            ],

            // ── tab_item ─────────────────────────────────────────────────────────
            [
                'block_key'         => 'tab_item',
                'name'              => 'Pestaña Individual',
                'description'       => 'Pestaña individual dentro de un bloque de Pestañas.',
                'category'          => 'content',
                'icon'              => 'file-text',
                'schema_definition' => json_encode([
                    'fields' => [
                        'title'   => ['type' => 'string',   'label' => 'Título de Pestaña', 'required' => true],
                        'content' => ['type' => 'richtext', 'label' => 'Contenido',         'required' => true],
                    ],
                    'config_fields' => [],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 121,
            ],

            // ── alert ────────────────────────────────────────────────────────────
            [
                'block_key'         => 'alert',
                'name'              => 'Alerta / Mensaje informativo',
                'description'       => 'Banner de notificación o aviso destacable.',
                'category'          => 'content',
                'icon'              => 'alert-circle',
                'schema_definition' => json_encode([
                    'fields' => [
                        'title'   => ['type' => 'string',   'label' => 'Título (Opcional)', 'required' => false],
                        'message' => ['type' => 'richtext', 'label' => 'Mensaje',           'required' => true],
                    ],
                    'config_fields' => [
                        'type' => [
                            'type'     => 'select',
                            'label'    => 'Tipo de Alerta',
                            'options'  => ['info', 'success', 'warning', 'danger'],
                            'default'  => 'info',
                            'required' => true,
                        ],
                        'dismissible' => ['type' => 'boolean', 'label' => 'Permitir cerrar (X)', 'required' => false, 'default' => true],
                        'css_class'   => ['type' => 'string',  'label' => 'Clase CSS',           'required' => false, 'default' => ''],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 1,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 130,
            ],

            // ── gallery ──────────────────────────────────────────────────────────
            [
                'block_key'         => 'gallery',
                'name'              => 'Galería de Imágenes (Contenedor)',
                'description'       => 'Contenedor para grilla de imágenes con visor lightbox.',
                'category'          => 'media',
                'icon'              => 'images',
                'schema_definition' => json_encode([
                    'fields' => [],
                    'config_fields' => [
                        'presentation_mode' => [
                            'type'     => 'select',
                            'label'    => 'Modo de Presentación',
                            'options'  => ['grid', 'inline_preview', 'modal_preview'],
                            'default'  => 'modal_preview',
                            'required' => false,
                        ],
                        'columns' => [
                            'type'     => 'select',
                            'label'    => 'Columnas',
                            'options'  => ['2', '3', '4', '6'],
                            'default'  => '3',
                            'required' => false,
                        ],
                        'gap' => [
                            'type'     => 'select',
                            'label'    => 'Espaciado',
                            'options'  => ['small', 'medium', 'large', 'none'],
                            'default'  => 'medium',
                            'required' => false,
                        ],
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                    'allowed_children' => ['gallery_item'],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 1,
                'is_active'        => 1,
                'sort_order'       => 140,
            ],

            // ── gallery_item ─────────────────────────────────────────────────────
            [
                'block_key'         => 'gallery_item',
                'name'              => 'Imagen de Galería',
                'description'       => 'Imagen individual para usar dentro de la Galería.',
                'category'          => 'media',
                'icon'              => 'image',
                'schema_definition' => json_encode([
                    'fields' => [
                        'image'   => ['type' => 'file',   'label' => 'Imagen',          'required' => true, 'accept' => 'image'],
                        'alt'     => ['type' => 'string', 'label' => 'Texto Alt (SEO)', 'required' => false],
                        'caption' => ['type' => 'string', 'label' => 'Leyenda/Título',  'required' => false],
                        'link_url'   => ['type' => 'url',    'label' => 'URL de destino', 'required' => false],
                        'link_label' => ['type' => 'string', 'label' => 'Texto del enlace', 'required' => false],
                    ],
                    'config_fields' => [],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 0,
                'is_container'     => 0,
                'is_active'        => 1,
                'sort_order'       => 141,
            ],
        ];

        foreach ($blocks as $block) {
            $this->upsertRecord('cms_content_blocks', [
                'block_key' => $block['block_key'],
            ], [
                'name'              => $block['name'],
                'description'       => $block['description'],
                'category'          => $block['category'],
                'icon'              => $block['icon'],
                'schema_definition' => $block['schema_definition'],
                'supports_pages'    => $block['supports_pages'],
                'supports_entries'  => $block['supports_entries'],
                'is_container'      => $block['is_container'],
                'is_active'         => $block['is_active'],
                'sort_order'        => $block['sort_order'],
            ]);
        }

    }
}
