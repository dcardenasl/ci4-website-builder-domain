<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Static catalog of all available block design templates.
 * Each entry describes a predefined design (block_key) with its default schema,
 * config_fields, and sample data for previewing.
 */
class BlockTemplateCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'key'         => 'hero_banner',
                'name'        => 'Hero Banner',
                'description' => 'Banner a ancho completo con imagen de fondo, título, subtítulo y botón de llamada a la acción.',
                'category'    => 'marketing',
                'icon'        => 'layout',
                'default_schema' => [
                    'fields' => [
                        'image_url'  => ['type' => 'url',    'label' => 'URL de la Imagen',   'required' => true],
                        'alt'        => ['type' => 'string', 'label' => 'Texto Alt',           'required' => false],
                        'heading'    => ['type' => 'string', 'label' => 'Título Principal',    'required' => true],
                        'subheading' => ['type' => 'string', 'label' => 'Subtítulo',           'required' => false],
                        'cta_label'  => ['type' => 'string', 'label' => 'Texto del Botón CTA', 'required' => false],
                        'cta_url'    => ['type' => 'url',    'label' => 'URL del Botón CTA',   'required' => false],
                    ],
                    'config_fields' => [
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                ],
                'preview_sample' => [
                    'image_url'  => 'https://placehold.co/1200x400/3b82f6/ffffff?text=Hero+Banner',
                    'alt'        => 'Imagen de fondo del hero',
                    'heading'    => 'Bienvenido a nuestro sitio',
                    'subheading' => 'Descubre todo lo que tenemos para ofrecerte',
                    'cta_label'  => 'Conoce más',
                    'cta_url'    => '#',
                ],
                'config_sample' => [
                    'css_class' => '',
                ],
            ],
            [
                'key'         => 'rich_text',
                'name'        => 'Texto Enriquecido',
                'description' => 'Bloque de texto con formato HTML completo. Ideal para artículos, descripciones y contenido editorial.',
                'category'    => 'content',
                'icon'        => 'align-left',
                'default_schema' => [
                    'fields' => [
                        'content' => ['type' => 'richtext', 'label' => 'Contenido', 'required' => true],
                    ],
                    'config_fields' => [
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                ],
                'preview_sample' => [
                    'content' => '<h2>Título de ejemplo</h2><p>Este es un párrafo de texto enriquecido. Puedes incluir <strong>negritas</strong>, <em>cursivas</em>, listas y más.</p><ul><li>Elemento uno</li><li>Elemento dos</li></ul>',
                ],
                'config_sample' => [
                    'css_class' => '',
                ],
            ],
            [
                'key'         => 'cta',
                'name'        => 'Llamada a la Acción (CTA)',
                'description' => 'Sección destacada con título, descripción y botón de acción. Ideal para convertir visitantes.',
                'category'    => 'marketing',
                'icon'        => 'mouse-pointer',
                'default_schema' => [
                    'fields' => [
                        'heading'     => ['type' => 'string',  'label' => 'Título',         'required' => true],
                        'text'        => ['type' => 'text',    'label' => 'Descripción',    'required' => false],
                        'label'       => ['type' => 'string',  'label' => 'Texto del Botón','required' => true],
                        'url'         => ['type' => 'url',     'label' => 'URL del Botón',  'required' => true],
                    ],
                    'config_fields' => [
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                        'variant'   => [
                            'type'    => 'select',
                            'label'   => 'Variante de Color',
                            'options' => ['blue', 'dark', 'light'],
                            'default' => 'blue',
                            'required' => false,
                        ],
                    ],
                ],
                'preview_sample' => [
                    'heading' => '¿Listo para comenzar?',
                    'text'    => 'Únete a miles de clientes satisfechos y empieza hoy mismo.',
                    'label'   => 'Comenzar ahora',
                    'url'     => '#',
                ],
                'config_sample' => [
                    'css_class' => '',
                    'variant'   => 'blue',
                ],
            ],
            [
                'key'         => 'image',
                'name'        => 'Imagen',
                'description' => 'Imagen individual con pie de foto opcional. Soporta imágenes de la biblioteca de medios.',
                'category'    => 'media',
                'icon'        => 'image',
                'default_schema' => [
                    'fields' => [
                        'file_id' => ['type' => 'integer', 'label' => 'ID de Archivo (Biblioteca)', 'required' => false],
                        'url'     => ['type' => 'url',     'label' => 'URL de la Imagen',           'required' => false],
                        'alt'     => ['type' => 'string',  'label' => 'Texto Alternativo',          'required' => false],
                        'caption' => ['type' => 'string',  'label' => 'Pie de Foto',                'required' => false],
                    ],
                    'config_fields' => [
                        'css_class'    => ['type' => 'string', 'label' => 'Clase CSS',        'required' => false, 'default' => ''],
                        'aspect_ratio' => [
                            'type'    => 'select',
                            'label'   => 'Proporción',
                            'options' => ['auto', '16/9', '4/3', '1/1'],
                            'default' => 'auto',
                            'required' => false,
                        ],
                    ],
                ],
                'preview_sample' => [
                    'file_id' => null,
                    'url'     => 'https://placehold.co/800x450/e2e8f0/94a3b8?text=Imagen',
                    'alt'     => 'Imagen de ejemplo',
                    'caption' => 'Pie de foto de ejemplo',
                ],
                'config_sample' => [
                    'css_class'    => '',
                    'aspect_ratio' => 'auto',
                ],
            ],
            [
                'key'         => 'container',
                'name'        => 'Contenedor',
                'description' => 'Bloque contenedor que agrupa y organiza bloques hijo. Útil para layouts en columnas o secciones con fondo.',
                'category'    => 'layout',
                'icon'        => 'layout-template',
                'default_schema' => [
                    'fields'       => [],
                    'config_fields' => [
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => 'container mx-auto px-4'],
                        'layout'    => [
                            'type'    => 'select',
                            'label'   => 'Distribución',
                            'options' => ['block', 'grid-2', 'grid-3', 'flex-row'],
                            'default' => 'block',
                            'required' => false,
                        ],
                    ],
                ],
                'preview_sample' => [],
                'config_sample'  => [
                    'css_class' => 'container mx-auto px-4',
                    'layout'    => 'block',
                ],
            ],
            // ── Teatro Museo / Sitio Web ────────────────────────────────────────
            [
                'key'         => 'hero_slider',
                'name'        => 'Carrusel Hero',
                'description' => 'Carrusel de diapositivas a ancho completo con posiciones configurables para texto y controles. Hasta 3 slides.',
                'category'    => 'marketing',
                'icon'        => 'gallery-horizontal',
                'default_schema' => [
                    'fields' => [
                        'slide_1_image_url' => ['type' => 'url',    'label' => 'Slide 1 — URL Imagen',   'required' => true],
                        'slide_1_heading'   => ['type' => 'string', 'label' => 'Slide 1 — Título',       'required' => true],
                        'slide_1_subtitle'  => ['type' => 'string', 'label' => 'Slide 1 — Subtítulo',    'required' => false],
                        'slide_1_cta_label' => ['type' => 'string', 'label' => 'Slide 1 — Texto Botón',  'required' => false],
                        'slide_1_cta_url'   => ['type' => 'url',    'label' => 'Slide 1 — URL Botón',    'required' => false],
                        'slide_2_image_url' => ['type' => 'url',    'label' => 'Slide 2 — URL Imagen',   'required' => false],
                        'slide_2_heading'   => ['type' => 'string', 'label' => 'Slide 2 — Título',       'required' => false],
                        'slide_2_subtitle'  => ['type' => 'string', 'label' => 'Slide 2 — Subtítulo',    'required' => false],
                        'slide_2_cta_label' => ['type' => 'string', 'label' => 'Slide 2 — Texto Botón',  'required' => false],
                        'slide_2_cta_url'   => ['type' => 'url',    'label' => 'Slide 2 — URL Botón',    'required' => false],
                        'slide_3_image_url' => ['type' => 'url',    'label' => 'Slide 3 — URL Imagen',   'required' => false],
                        'slide_3_heading'   => ['type' => 'string', 'label' => 'Slide 3 — Título',       'required' => false],
                        'slide_3_subtitle'  => ['type' => 'string', 'label' => 'Slide 3 — Subtítulo',    'required' => false],
                        'slide_3_cta_label' => ['type' => 'string', 'label' => 'Slide 3 — Texto Botón',  'required' => false],
                        'slide_3_cta_url'   => ['type' => 'url',    'label' => 'Slide 3 — URL Botón',    'required' => false],
                    ],
                    'config_fields' => [
                        'autoplay'        => ['type' => 'boolean', 'label' => 'Reproducción automática', 'required' => false, 'default' => true],
                        'interval'        => ['type' => 'number',  'label' => 'Intervalo (ms)',           'required' => false, 'default' => 5000],
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
                ],
                'preview_sample' => [
                    'slide_1_image_url' => self::placeholderSlide('Temporada 2026', '#e5e7eb', '#111827'),
                    'slide_1_heading'   => 'Temporada 2026',
                    'slide_1_subtitle'  => 'Programación destacada y actividades especiales.',
                    'slide_1_cta_label' => 'Ver programación',
                    'slide_1_cta_url'   => '/featured',
                    'slide_2_image_url' => self::placeholderSlide('Exposiciones', '#dbeafe', '#0f172a'),
                    'slide_2_heading'   => 'Exposiciones',
                    'slide_2_subtitle'  => 'Recorridos, talleres y experiencias.',
                    'slide_2_cta_label' => 'Explorar',
                    'slide_2_cta_url'   => '/collection',
                    'slide_3_image_url' => self::placeholderSlide('Entradas', '#f3f4f6', '#111827'),
                    'slide_3_heading'   => 'Entradas',
                    'slide_3_subtitle'  => 'Reserva tu visita en pocos pasos.',
                    'slide_3_cta_label' => 'Conocer más',
                    'slide_3_cta_url'   => '/news',
                ],
                'config_sample' => [
                    'autoplay'          => true,
                    'interval'          => 5000,
                    'overlay_opacity'   => '0',
                    'caption_position'  => 'below',
                    'controls_position' => 'below',
                    'css_class'         => '',
                ],
            ],
            [
                'key'         => 'collection_grid',
                'name'        => 'Grilla de Colección',
                'description' => 'Lista entradas publicadas desde cualquier colección del CMS con límite, orden y variante visual configurables.',
                'category'    => 'content',
                'icon'        => 'layout-grid',
                'default_schema' => [
                    'fields' => [
                        'section_title'    => ['type' => 'string', 'label' => 'Título de sección',               'required' => false],
                        'section_subtitle' => ['type' => 'string', 'label' => 'Subtítulo de sección',            'required' => false],
                        'view_all_label'   => ['type' => 'string', 'label' => 'Texto "Ver todos"',               'required' => false],
                        'view_all_url'     => ['type' => 'url',    'label' => 'URL "Ver todos"',                 'required' => false],
                        'empty_message'    => ['type' => 'string', 'label' => 'Mensaje cuando no hay contenido', 'required' => false],
                    ],
                    'config_fields' => [
                        'collection_key'  => ['type' => 'string', 'label' => 'Clave de Colección (CMS)', 'required' => true,  'default' => ''],
                        'items_limit'     => ['type' => 'number', 'label' => 'Máx. elementos',           'required' => false, 'default' => 3],
                        'order_by'        => ['type' => 'select', 'label' => 'Ordenar por',              'required' => false, 'options' => ['published_at', 'sort_order', 'created_at', 'title'], 'default' => 'published_at'],
                        'order_direction' => ['type' => 'select', 'label' => 'Dirección',                'required' => false, 'options' => ['asc', 'desc'], 'default' => 'desc'],
                        'layout_variant'  => ['type' => 'select', 'label' => 'Variante visual',          'required' => false, 'options' => ['cards', 'compact', 'portfolio'], 'default' => 'cards'],
                        'css_class'       => ['type' => 'string', 'label' => 'Clase CSS',                'required' => false, 'default' => ''],
                    ],
                ],
                'preview_sample' => [
                    'section_title'    => 'Contenido destacado',
                    'section_subtitle' => 'Últimas publicaciones de la colección seleccionada.',
                    'view_all_label'   => 'Ver todo',
                    'view_all_url'     => '/coleccion',
                    'empty_message'    => 'No hay contenido publicado por el momento.',
                ],
                'config_sample' => [
                    'collection_key'  => 'noticias',
                    'items_limit'     => 3,
                    'order_by'        => 'published_at',
                    'order_direction' => 'desc',
                    'layout_variant'  => 'cards',
                    'css_class'       => '',
                ],
            ],
            [
                'key'         => 'collection_listing',
                'name'        => 'Listado de Colección',
                'description' => 'Listado completo de una colección con filtros, búsqueda y paginación.',
                'category'    => 'content',
                'icon'        => 'list-tree',
                'default_schema' => [
                    'fields' => [
                        'intro_title'   => ['type' => 'string', 'label' => 'Título introductorio', 'required' => false],
                        'intro_text'    => ['type' => 'richtext', 'label' => 'Texto introductorio', 'required' => false],
                        'empty_message' => ['type' => 'string', 'label' => 'Mensaje vacío', 'required' => false],
                    ],
                    'config_fields' => [
                        'collection_id'   => ['type' => 'select', 'label' => 'Colección', 'required' => true, 'options' => [], 'default' => ''],
                        'per_page'        => ['type' => 'number', 'label' => 'Elementos por página', 'required' => false, 'default' => 12],
                        'order_by'        => ['type' => 'select', 'label' => 'Ordenar por', 'required' => false, 'options' => ['published_at', 'sort_order', 'created_at', 'title'], 'default' => 'published_at'],
                        'order_direction' => ['type' => 'select', 'label' => 'Dirección', 'required' => false, 'options' => ['asc', 'desc'], 'default' => 'desc'],
                        'layout_variant'  => ['type' => 'select', 'label' => 'Variante visual', 'required' => false, 'options' => ['cards', 'compact', 'portfolio'], 'default' => 'cards'],
                        'show_search'     => ['type' => 'boolean', 'label' => 'Mostrar búsqueda', 'required' => false, 'default' => true],
                        'show_categories' => ['type' => 'boolean', 'label' => 'Mostrar categorías', 'required' => false, 'default' => true],
                        'show_tags'       => ['type' => 'boolean', 'label' => 'Mostrar etiquetas', 'required' => false, 'default' => false],
                        'css_class'       => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                ],
                'preview_sample' => [
                    'intro_title'   => 'Listado completo',
                    'intro_text'    => '<p>Usa este bloque para mostrar el índice público de una colección.</p>',
                    'empty_message' => 'No hay contenido disponible.',
                ],
                'config_sample' => [
                    'collection_id'   => 0,
                    'per_page'        => 12,
                    'order_by'        => 'published_at',
                    'order_direction' => 'desc',
                    'layout_variant'  => 'cards',
                    'show_search'     => true,
                    'show_categories' => true,
                    'show_tags'       => false,
                    'css_class'       => '',
                ],
            ],
            [
                'key'         => 'page_header',
                'name'        => 'Encabezado de Página',
                'description' => 'Encabezado de sección con título principal y migas de pan (breadcrumb). Ideal para páginas internas.',
                'category'    => 'navigation',
                'icon'        => 'heading',
                'default_schema' => [
                    'fields' => [
                        'heading'           => ['type' => 'string', 'label' => 'Título',              'required' => true],
                        'subheading'        => ['type' => 'string', 'label' => 'Subtítulo',           'required' => false],
                        'breadcrumb_label'  => ['type' => 'string', 'label' => 'Etiqueta breadcrumb', 'required' => false],
                        'breadcrumb_url'    => ['type' => 'string', 'label' => 'URL breadcrumb',      'required' => false],
                    ],
                    'config_fields' => [
                        'bg_color'  => ['type' => 'string', 'label' => 'Color de fondo (Tailwind)',  'required' => false, 'default' => 'bg-gray-100'],
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS',                 'required' => false, 'default' => ''],
                    ],
                ],
                'preview_sample' => [
                    'heading'          => 'Contact Us',
                    'subheading'       => 'We\'d love to hear from you',
                    'breadcrumb_label' => 'Home',
                    'breadcrumb_url'   => '/',
                ],
                'config_sample' => [
                    'bg_color'  => 'bg-gray-100',
                    'css_class' => '',
                ],
            ],
            [
                'key'         => 'form_embed',
                'name'        => 'Formulario Embebido',
                'description' => 'Formulario dinámico configurado desde el sistema de formularios.',
                'category'    => 'interactive',
                'icon'        => 'mail',
                'default_schema' => [
                    'fields' => [],
                    'config_fields' => [
                        'form_key'  => ['type' => 'string', 'label' => 'Formulario', 'required' => true, 'default' => 'contact'],
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                ],
                'preview_sample' => [],
                'config_sample' => [
                    'form_key'  => 'contact',
                    'css_class' => '',
                ],
            ],
            [
                'key'         => 'contact_info',
                'name'        => 'Información de Contacto',
                'description' => 'Datos estructurados de contacto y horarios.',
                'category'    => 'contact',
                'icon'        => 'map-pin',
                'default_schema' => [
                    'fields' => [
                        'section_title'       => ['type' => 'string', 'label' => 'Título de sección',       'required' => false],
                        'section_description' => ['type' => 'text',   'label' => 'Descripción de sección',  'required' => false],
                        'address_label' => ['type' => 'string', 'label' => 'Etiqueta Dirección',  'required' => false],
                        'address'       => ['type' => 'string', 'label' => 'Dirección',            'required' => false],
                        'phone_label'   => ['type' => 'string', 'label' => 'Etiqueta Teléfono',   'required' => false],
                        'phone'         => ['type' => 'string', 'label' => 'Teléfono',             'required' => false],
                        'email_label'   => ['type' => 'string', 'label' => 'Etiqueta Email',      'required' => false],
                        'email'         => ['type' => 'string', 'label' => 'Email',               'required' => false],
                        'hours_label'   => ['type' => 'string', 'label' => 'Etiqueta Horarios',   'required' => false],
                        'hours'         => ['type' => 'text',   'label' => 'Horarios',             'required' => false],
                    ],
                    'config_fields' => [
                        'layout'    => ['type' => 'select', 'label' => 'Layout', 'options' => ['stacked', 'two_columns'], 'default' => 'stacked', 'required' => false],
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                ],
                'preview_sample' => [
                    'section_title'       => 'Contacto',
                    'section_description' => 'Canales oficiales para escribirnos o visitarnos.',
                    'address_label' => 'Address',
                    'address'       => '123 Main Street, Your City, Country',
                    'phone_label'   => 'Phone',
                    'phone'         => '+1 (555) 000-0000',
                    'email_label'   => 'Email',
                    'email'         => 'hola@example.com',
                    'hours_label'   => 'Office Hours',
                    'hours'         => "Monday to Friday: 9:00 - 18:00\nSaturday: 10:00 - 14:00",
                ],
                'config_sample' => [
                    'layout'    => 'stacked',
                    'css_class' => '',
                ],
            ],
            [
                'key'         => 'map_embed',
                'name'        => 'Mapa Embebido',
                'description' => 'Iframe de mapa configurable e independiente de los datos de contacto.',
                'category'    => 'contact',
                'icon'        => 'map',
                'default_schema' => [
                    'fields' => [
                        'title'   => ['type' => 'string', 'label' => 'Título', 'required' => false],
                        'caption' => ['type' => 'string', 'label' => 'Texto de apoyo', 'required' => false],
                    ],
                    'config_fields' => [
                        'embed_url'    => ['type' => 'url', 'label' => 'URL Embed', 'required' => true, 'default' => ''],
                        'aspect_ratio' => ['type' => 'select', 'label' => 'Proporción', 'options' => ['16/9', '4/3', '1/1'], 'default' => '16/9', 'required' => false],
                        'height'       => ['type' => 'number', 'label' => 'Alto fallback (px)', 'required' => false, 'default' => 360],
                        'css_class'    => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                ],
                'preview_sample' => [
                    'title'   => 'Dónde estamos',
                    'caption' => 'Encuentra nuestra ubicación principal.',
                ],
                'config_sample' => [
                    'embed_url'    => '',
                    'aspect_ratio' => '16/9',
                    'height'       => 360,
                    'css_class'    => '',
                ],
            ],
            [
                'key'         => 'social_links',
                'name'        => 'Redes Sociales',
                'description' => 'Bloque con enlaces y handles de redes sociales: Facebook, Instagram, Twitter/X y YouTube.',
                'category'    => 'social',
                'icon'        => 'share-2',
                'default_schema' => [
                    'fields' => [
                        'heading' => ['type' => 'string', 'label' => 'Título de sección', 'required' => false],
                    ],
                    'config_fields' => [
                        'facebook_url'      => ['type' => 'url',    'label' => 'URL Facebook',       'required' => false, 'default' => ''],
                        'facebook_handle'   => ['type' => 'string', 'label' => 'Handle Facebook',    'required' => false, 'default' => ''],
                        'instagram_url'     => ['type' => 'url',    'label' => 'URL Instagram',      'required' => false, 'default' => ''],
                        'instagram_handle'  => ['type' => 'string', 'label' => 'Handle Instagram',   'required' => false, 'default' => ''],
                        'twitter_url'       => ['type' => 'url',    'label' => 'URL Twitter/X',      'required' => false, 'default' => ''],
                        'youtube_url'       => ['type' => 'url',    'label' => 'URL YouTube',        'required' => false, 'default' => ''],
                        'css_class'         => ['type' => 'string', 'label' => 'Clase CSS',          'required' => false, 'default' => ''],
                    ],
                ],
                'preview_sample' => [
                    'heading' => 'Síguenos',
                ],
                'config_sample' => [
                    'facebook_url'     => '',
                    'facebook_handle'  => '',
                    'instagram_url'    => '',
                    'instagram_handle' => '',
                    'twitter_url'      => '',
                    'youtube_url'      => '',
                    'css_class'        => '',
                ],
            ],
            [
                'key'         => 'metrics_grid',
                'name'        => 'Grilla de Métricas',
                'description' => 'Contenedor para KPIs, cifras o hitos con variantes visuales.',
                'category'    => 'content',
                'icon'        => 'bar-chart-3',
                'default_schema' => [
                    'fields' => [
                        'section_title'    => ['type' => 'string', 'label' => 'Título de sección', 'required' => false],
                        'section_subtitle' => ['type' => 'text', 'label' => 'Subtítulo de sección', 'required' => false],
                    ],
                    'config_fields' => [
                        'variant'   => ['type' => 'select', 'label' => 'Variante', 'options' => ['light', 'dark', 'primary'], 'default' => 'light', 'required' => false],
                        'columns'   => ['type' => 'select', 'label' => 'Columnas', 'options' => ['2', '3', '4'], 'default' => '3', 'required' => false],
                        'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                    'allowed_children' => ['metric_item'],
                ],
                'preview_sample' => [
                    'section_title'    => 'Métricas destacadas',
                    'section_subtitle' => 'Indicadores clave del proyecto.',
                ],
                'config_sample' => [
                    'variant'   => 'light',
                    'columns'   => '3',
                    'css_class' => '',
                ],
            ],
            [
                'key'         => 'metric_item',
                'name'        => 'Métrica',
                'description' => 'KPI configurable con prefijo, número, sufijo, descripción y fuente opcional.',
                'category'    => 'content',
                'icon'        => 'hash',
                'default_schema' => [
                    'fields' => [
                        'prefix'       => ['type' => 'string', 'label' => 'Prefijo', 'required' => false],
                        'number'       => ['type' => 'string', 'label' => 'Número', 'required' => true],
                        'suffix'       => ['type' => 'string', 'label' => 'Sufijo', 'required' => false],
                        'label'        => ['type' => 'string', 'label' => 'Etiqueta', 'required' => true],
                        'description'  => ['type' => 'text', 'label' => 'Descripción', 'required' => false],
                        'source_label' => ['type' => 'string', 'label' => 'Fuente', 'required' => false],
                        'source_url'   => ['type' => 'url', 'label' => 'URL fuente', 'required' => false],
                        'icon'         => ['type' => 'string', 'label' => 'Icono', 'required' => false],
                    ],
                ],
                'preview_sample' => [
                    'prefix'       => '',
                    'number'       => '120',
                    'suffix'       => '+',
                    'label'        => 'Proyectos',
                    'description'  => 'Proyectos gestionados desde el CMS.',
                    'source_label' => 'Registro institucional',
                    'source_url'   => '',
                    'icon'         => 'sparkles',
                ],
                'config_sample' => [],
            ],
            [
                'key'         => 'cards_slider',
                'name'        => 'Slider de Tarjetas',
                'description' => 'Carrusel genérico para tarjetas editoriales, testimoniales o multimedia.',
                'category'    => 'content',
                'icon'        => 'gallery-horizontal-end',
                'default_schema' => [
                    'fields' => [
                        'section_title'    => ['type' => 'string', 'label' => 'Título de sección', 'required' => false],
                        'section_subtitle' => ['type' => 'text', 'label' => 'Subtítulo de sección', 'required' => false],
                    ],
                    'config_fields' => [
                        'autoplay'      => ['type' => 'boolean', 'label' => 'Autoplay', 'required' => false, 'default' => true],
                        'interval'      => ['type' => 'number', 'label' => 'Intervalo (ms)', 'required' => false, 'default' => 6000],
                        'visible_count' => ['type' => 'select', 'label' => 'Tarjetas visibles', 'options' => ['1', '2', '3'], 'default' => '1', 'required' => false],
                        'card_variant'  => ['type' => 'select', 'label' => 'Variante de tarjeta', 'options' => ['editorial', 'testimonial', 'media'], 'default' => 'editorial', 'required' => false],
                        'css_class'     => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                    ],
                    'allowed_children' => ['slide_card'],
                ],
                'preview_sample' => [
                    'section_title'    => 'Historias destacadas',
                    'section_subtitle' => 'Tarjetas configurables para distintos usos.',
                ],
                'config_sample' => [
                    'autoplay'      => true,
                    'interval'      => 6000,
                    'visible_count' => '1',
                    'card_variant'  => 'editorial',
                    'css_class'     => '',
                ],
            ],
            [
                'key'         => 'slide_card',
                'name'        => 'Tarjeta de Slider',
                'description' => 'Tarjeta reutilizable con texto, metadata, imagen, rating y CTA opcionales.',
                'category'    => 'content',
                'icon'        => 'square',
                'default_schema' => [
                    'fields' => [
                        'eyebrow'          => ['type' => 'string', 'label' => 'Etiqueta superior', 'required' => false],
                        'title'            => ['type' => 'string', 'label' => 'Título', 'required' => false],
                        'body'             => ['type' => 'text', 'label' => 'Texto', 'required' => false],
                        'meta_title'       => ['type' => 'string', 'label' => 'Título metadata', 'required' => false],
                        'meta_description' => ['type' => 'string', 'label' => 'Descripción metadata', 'required' => false],
                        'image'            => ['type' => 'file', 'label' => 'Imagen', 'required' => false, 'accept' => 'image/*'],
                        'rating'           => ['type' => 'select', 'label' => 'Rating', 'options' => ['0', '1', '2', '3', '4', '5'], 'default' => '0', 'required' => false],
                        'link_url'         => ['type' => 'url', 'label' => 'URL CTA', 'required' => false],
                        'link_label'       => ['type' => 'string', 'label' => 'Texto CTA', 'required' => false],
                    ],
                ],
                'preview_sample' => [
                    'eyebrow'          => 'Caso destacado',
                    'title'            => 'Una tarjeta flexible',
                    'body'             => 'Contenido adaptable para testimonios, pasos, historias o beneficios.',
                    'meta_title'       => 'Equipo editorial',
                    'meta_description' => 'Contenido CMS',
                    'rating'           => '0',
                    'link_url'         => '#',
                    'link_label'       => 'Ver más',
                ],
                'config_sample' => [],
            ],
        ];
    }

    /**
     * Generate a data URI for a placeholder SVG slide.
     */
    private static function placeholderSlide(string $label, string $background, string $foreground): string
    {
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500"><rect width="1200" height="500" fill="%s"/><text x="50%%" y="50%%" fill="%s" font-family="Arial,Helvetica,sans-serif" font-size="56" font-weight="700" text-anchor="middle" dominant-baseline="middle">%s</text></svg>',
            htmlspecialchars($background, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            htmlspecialchars($foreground, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            htmlspecialchars($label, ENT_QUOTES | ENT_XML1, 'UTF-8')
        );

        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByKey(string $key): ?array
    {
        foreach (static::all() as $template) {
            if ($template['key'] === $key) {
                return $template;
            }
        }
        return null;
    }
}
