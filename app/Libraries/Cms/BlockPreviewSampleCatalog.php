<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Curated preview samples for block schema/template projections (used by the
 * "start from template" designer UI in the admin block-type screens).
 *
 * Keep these aligned with the visible preview UX rather than with any one
 * controller. The public web app (ci4-website-builder-web) maintains its own
 * copy in app/Common.php for its live block-preview renderer — the two apps
 * are independently deployable repos and must not share source files across
 * the repo boundary, so this catalog is intentionally mirrored, not shared,
 * the same way block schemas/views already are between the two repos.
 */
final class BlockPreviewSampleCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function samples(): array
    {
        return [
            'hero_banner' => [
                'alt' => 'Imagen de fondo del hero',
                'heading' => 'Previsualización de Banner',
                'subheading' => 'Este banner utiliza las tipografías y el diseño completo de tu sitio público.',
                'cta_label' => 'Acción Principal',
                'cta_url' => '#',
            ],
            'rich_text' => [
                'content' => '<h2>Título de ejemplo</h2><p>Este es un bloque de texto enriquecido de ejemplo. Puedes incluir <strong>negritas</strong>, <em>cursivas</em>, listas y más.</p><ul><li>Elemento uno</li><li>Elemento dos</li></ul>',
            ],
            'cta' => [
                'heading' => '¿Listo para comenzar?',
                'text' => 'Únete a miles de clientes satisfechos y empieza hoy mismo.',
                'label' => 'Comenzar ahora',
                'url' => '#',
            ],
            'slide_banner' => [
                'heading' => 'Temporada 2026',
                'subtitle' => 'Programación destacada y actividades especiales.',
                'cta_label' => 'Ver programación',
                'cta_url' => '/featured',
            ],
            'accordion_item' => [
                'title' => '¿Cómo funciona la vista previa?',
                'content' => '<p>La vista previa renderiza el componente real usando el motor de plantillas público.</p>',
            ],
            'gallery_item' => [
                'alt' => 'Imagen de ejemplo',
                'caption' => 'Imagen destacada',
                'link_url' => '#',
                'link_label' => 'Ver imagen',
            ],
            'tab_item' => [
                'title' => 'Pestaña de Ejemplo 1',
                'content' => '<p>Este es el contenido de la primera pestaña de ejemplo.</p>',
            ],
            'card_item' => [
                'title' => 'Tarjeta de ejemplo',
                'description' => 'Descripción breve de la tarjeta.',
                'link_url' => '#',
                'link_label' => 'Ver más',
            ],
            'slide_card' => [
                'eyebrow' => 'Caso destacado',
                'title' => 'Tarjeta Deslizable 1',
                'body' => 'Descripción breve para la tarjeta de ejemplo en el slider.',
                'meta_title' => 'Equipo editorial',
                'meta_description' => 'Contenido CMS',
                'rating' => '0',
                'link_url' => '#',
                'link_label' => 'Ver más',
            ],
            'metric_item' => [
                'prefix' => '',
                'number' => '120',
                'suffix' => '+',
                'label' => 'Proyectos Completados',
                'description' => 'Proyectos gestionados desde el CMS.',
                'source_label' => 'Registro institucional',
                'source_url' => '',
                'icon' => 'sparkles',
            ],
            'asset_item' => [
                'name' => 'Caso de Éxito PDF',
                'link_url' => '#',
            ],
            'social_link_item' => [
                'handle' => '@example',
            ],
            'image' => [
                'alt' => 'Imagen de ejemplo',
                'caption' => 'Pie de foto de ejemplo',
            ],
            'collection_grid' => [
                'section_title' => 'Contenido destacado',
                'section_subtitle' => 'Últimas publicaciones de la colección seleccionada.',
                'view_all_label' => 'Ver todo',
                'view_all_url' => '/coleccion',
                'empty_message' => 'No hay contenido publicado por el momento.',
            ],
            'collection_listing' => [
                'intro_title' => 'Listado completo',
                'intro_text' => '<p>Usa este bloque para mostrar el índice público de una colección.</p>',
                'empty_message' => 'No hay contenido disponible.',
            ],
            'pricing_plan' => [
                'name' => 'Plan Básico',
                'price' => '$29',
                'period' => '/ mes',
                'description' => 'Ideal para comenzar.',
                'features' => '<ul><li>1 proyecto</li><li>Soporte por correo</li></ul>',
                'cta_label' => 'Comenzar',
                'cta_url' => '#',
            ],
            'video_player' => [
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'heading' => 'Video de Presentación de Ejemplo',
            ],
            'alert' => [
                'title' => 'Aviso Importante',
                'message' => '<p>Este es un mensaje de alerta de ejemplo para mostrar cómo se ve el diseño en tu sitio público.</p>',
            ],
            'page_header' => [
                'heading' => 'Contact Us',
                'subheading' => 'We\'d love to hear from you',
                'breadcrumb_label' => 'Home',
                'breadcrumb_url' => '/',
            ],
            'contact_info' => [
                'section_title' => 'Contacto',
                'section_description' => 'Canales oficiales para escribirnos o visitarnos.',
                'address_label' => 'Address',
                'address' => '123 Main Street, Your City, Country',
                'phone_label' => 'Phone',
                'phone' => '+1 (555) 000-0000',
                'email_label' => 'Email',
                'email' => 'hola@example.com',
                'hours_label' => 'Office Hours',
                'hours' => "Monday to Friday: 9:00 - 18:00\nSaturday: 10:00 - 14:00",
            ],
            'map_embed' => [
                'title' => 'Nuestra Ubicación',
                'caption' => 'Valparaíso, Chile',
            ],
            'social_links' => [
                'heading' => 'Síguenos',
            ],
            'metrics_grid' => [
                'section_title' => 'Métricas destacadas',
                'section_subtitle' => 'Indicadores clave del proyecto.',
            ],
            'cards_slider' => [
                'section_title' => 'Historias destacadas',
                'section_subtitle' => 'Tarjetas configurables para distintos usos.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function sample(string $blockKey): array
    {
        return self::samples()[$blockKey] ?? [];
    }
}
