<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

final class CmsPresetCatalog
{
    /**
     * @return list<string>
     */
    public static function collectionTypes(): array
    {
        return ['blog', 'news', 'portfolio', 'services', 'other'];
    }

    /**
     * @return list<string>
     */
    public static function pageTypes(): array
    {
        return ['home', 'generic', 'contact', 'privacy', 'terms', '404', '500', 'maintenance', 'about', 'history', 'events'];
    }

    /**
     * @return array<int, array{type_key: string, label: string, version: string, block_template: array<string, mixed>, wizard_config: array<string, mixed>|null}>
     */
    public static function collectionPresets(): array
    {
        return [
            self::collectionPreset('blog', [
                ['block_key' => 'rich_text', 'label' => 'Introducción', 'help_text' => 'Primer bloque editorial', 'required' => true, 'locked' => false, 'block_config_defaults' => new \stdClass()],
                ['block_key' => 'image', 'label' => 'Imagen destacada', 'help_text' => 'Apoyo visual para la entrada', 'required' => false, 'locked' => false, 'block_config_defaults' => new \stdClass()],
            ], [
                ['step_title' => 'Título', 'step_hint' => 'Define el nombre de la entrada', 'fields' => [['key' => 'title', 'label' => 'Título', 'type' => 'text', 'required' => true]]],
                ['step_title' => 'Resumen', 'step_hint' => 'Aporta contexto breve', 'fields' => [['key' => 'excerpt', 'label' => 'Resumen', 'type' => 'textarea', 'required' => false]]],
            ]),
            self::collectionPreset('news', [
                ['block_key' => 'rich_text', 'label' => 'Titular', 'help_text' => 'Bloque principal de la noticia', 'required' => true, 'locked' => true, 'block_config_defaults' => new \stdClass()],
                ['block_key' => 'image', 'label' => 'Imagen de portada', 'help_text' => 'Acompaña la noticia con una imagen', 'required' => false, 'locked' => false, 'block_config_defaults' => new \stdClass()],
            ], [
                ['step_title' => 'Titular', 'step_hint' => 'Título visible para la noticia', 'fields' => [['key' => 'title', 'label' => 'Titular', 'type' => 'text', 'required' => true]]],
                ['step_title' => 'Resumen', 'step_hint' => 'Una breve bajada informativa', 'fields' => [['key' => 'excerpt', 'label' => 'Resumen', 'type' => 'textarea', 'required' => false]]],
            ]),
            self::collectionPreset('portfolio', [
                ['block_key' => 'image', 'label' => 'Proyecto', 'help_text' => 'Imagen o captura del trabajo', 'required' => true, 'locked' => false, 'block_config_defaults' => new \stdClass()],
                ['block_key' => 'rich_text', 'label' => 'Descripción', 'help_text' => 'Resumen de lo realizado', 'required' => false, 'locked' => false, 'block_config_defaults' => new \stdClass()],
            ], [
                ['step_title' => 'Nombre', 'step_hint' => 'Identifica el proyecto', 'fields' => [['key' => 'title', 'label' => 'Nombre', 'type' => 'text', 'required' => true]]],
            ]),
            self::collectionPreset('services', [
                ['block_key' => 'rich_text', 'label' => 'Servicio', 'help_text' => 'Descripción principal del servicio', 'required' => true, 'locked' => false, 'block_config_defaults' => new \stdClass()],
                ['block_key' => 'cta', 'label' => 'Llamado a la acción', 'help_text' => 'Invita a contactar o cotizar', 'required' => false, 'locked' => false, 'block_config_defaults' => new \stdClass()],
            ], [
                ['step_title' => 'Nombre', 'step_hint' => 'Nombre del servicio', 'fields' => [['key' => 'title', 'label' => 'Nombre', 'type' => 'text', 'required' => true]]],
                ['step_title' => 'Descripción', 'step_hint' => 'Breve explicación', 'fields' => [['key' => 'excerpt', 'label' => 'Descripción', 'type' => 'textarea', 'required' => false]]],
            ]),
            self::collectionPreset('other', [
                ['block_key' => 'rich_text', 'label' => 'Contenido', 'help_text' => 'Punto de partida genérico', 'required' => true, 'locked' => false, 'block_config_defaults' => new \stdClass()],
            ], [
                ['step_title' => 'Título', 'step_hint' => 'Nombre visible de la entrada', 'fields' => [['key' => 'title', 'label' => 'Título', 'type' => 'text', 'required' => true]]],
            ]),
        ];
    }

    /**
     * @return array<int, array{type_key: string, label: string, version: string, block_template: array<string, mixed>, wizard_config: null}>
     */
    public static function pagePresets(): array
    {
        return [
            self::pagePreset('home', [
                ['block_key' => 'hero_slider', 'label' => 'Hero principal', 'help_text' => 'Bloque de bienvenida', 'required' => true, 'locked' => false, 'block_config_defaults' => (object) []],
                ['block_key' => 'news_grid', 'label' => 'Últimas noticias', 'help_text' => 'Sección de contenido reciente', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['collection_key' => 'noticias', 'items_limit' => 3, 'css_class' => '']],
                ['block_key' => 'cta', 'label' => 'Llamado a la acción', 'help_text' => 'Invitación final', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['variant' => 'blue', 'css_class' => '']],
            ]),
            self::pagePreset('generic', [
                ['block_key' => 'page_header', 'label' => 'Encabezado', 'help_text' => 'Título y breadcrumb', 'required' => true, 'locked' => false, 'block_config_defaults' => (object) ['bg_color' => 'bg-gray-100', 'css_class' => '']],
                ['block_key' => 'rich_text', 'label' => 'Contenido', 'help_text' => 'Bloque editorial principal', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['css_class' => '']],
            ]),
            self::pagePreset('contact', [
                ['block_key' => 'page_header', 'label' => 'Encabezado', 'help_text' => 'Título de la página', 'required' => true, 'locked' => false, 'block_config_defaults' => (object) ['bg_color' => 'bg-gray-100', 'css_class' => '']],
                ['block_key' => 'contact_form', 'label' => 'Formulario', 'help_text' => 'Formulario de contacto', 'required' => true, 'locked' => false, 'block_config_defaults' => (object) ['form_key' => 'contact', 'show_info_boxes' => true, 'css_class' => '']],
                ['block_key' => 'location_info', 'label' => 'Ubicación', 'help_text' => 'Datos de contacto y mapa', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['map_embed_url' => '', 'css_class' => '']],
                ['block_key' => 'social_links', 'label' => 'Redes', 'help_text' => 'Enlaces sociales', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['css_class' => '']],
            ]),
            self::pagePreset('privacy', [
                ['block_key' => 'page_header', 'label' => 'Encabezado', 'help_text' => 'Título y breadcrumb', 'required' => true, 'locked' => false, 'block_config_defaults' => (object) ['bg_color' => 'bg-gray-100', 'css_class' => '']],
                ['block_key' => 'rich_text', 'label' => 'Política', 'help_text' => 'Texto legal', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['css_class' => '']],
            ]),
            self::pagePreset('terms', [
                ['block_key' => 'page_header', 'label' => 'Encabezado', 'help_text' => 'Título y breadcrumb', 'required' => true, 'locked' => false, 'block_config_defaults' => (object) ['bg_color' => 'bg-gray-100', 'css_class' => '']],
                ['block_key' => 'rich_text', 'label' => 'Términos', 'help_text' => 'Texto legal', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['css_class' => '']],
            ]),
            self::pagePreset('about', [
                ['block_key' => 'page_header', 'label' => 'Encabezado', 'help_text' => 'Título de la página', 'required' => true, 'locked' => false, 'block_config_defaults' => (object) ['bg_color' => 'bg-gray-100', 'css_class' => '']],
                ['block_key' => 'rich_text', 'label' => 'Introducción', 'help_text' => 'Bloque editorial base', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['css_class' => '']],
                ['block_key' => 'image', 'label' => 'Imagen', 'help_text' => 'Apoyo visual', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['aspect_ratio' => '16/9', 'css_class' => '']],
                ['block_key' => 'cta', 'label' => 'Llamado a la acción', 'help_text' => 'Invitación final', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['variant' => 'blue', 'css_class' => '']],
            ]),
            self::pagePreset('history', [
                ['block_key' => 'page_header', 'label' => 'Encabezado', 'help_text' => 'Título de la página', 'required' => true, 'locked' => false, 'block_config_defaults' => (object) ['bg_color' => 'bg-gray-100', 'css_class' => '']],
                ['block_key' => 'rich_text', 'label' => 'Historia', 'help_text' => 'Bloque editorial base', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['css_class' => '']],
                ['block_key' => 'image', 'label' => 'Imagen', 'help_text' => 'Apoyo visual', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['aspect_ratio' => '16/9', 'css_class' => '']],
                ['block_key' => 'stats_section', 'label' => 'Estadísticas', 'help_text' => 'Contenedor de hitos', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['variant' => 'dark', 'css_class' => '']],
                ['block_key' => 'faq_accordion', 'label' => 'Preguntas frecuentes', 'help_text' => 'Contenedor de FAQ', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['css_class' => '']],
                ['block_key' => 'cta', 'label' => 'Llamado a la acción', 'help_text' => 'Invitación final', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['variant' => 'blue', 'css_class' => '']],
            ]),
            self::pagePreset('events', [
                ['block_key' => 'page_header', 'label' => 'Encabezado', 'help_text' => 'Título de la página', 'required' => true, 'locked' => false, 'block_config_defaults' => (object) ['bg_color' => 'bg-gray-100', 'css_class' => '']],
                ['block_key' => 'events_grid', 'label' => 'Cartelera', 'help_text' => 'Bloque de eventos relacionados', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['collection_key' => 'cartelera', 'items_limit' => 6, 'css_class' => '']],
                ['block_key' => 'image', 'label' => 'Imagen', 'help_text' => 'Apoyo visual', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['aspect_ratio' => '16/9', 'css_class' => '']],
            ]),
            self::pagePreset('maintenance', [
                ['block_key' => 'page_header', 'label' => 'Encabezado', 'help_text' => 'Título de la página', 'required' => true, 'locked' => false, 'block_config_defaults' => (object) ['bg_color' => 'bg-gray-100', 'css_class' => '']],
                ['block_key' => 'rich_text', 'label' => 'Mensaje', 'help_text' => 'Aviso temporal', 'required' => false, 'locked' => false, 'block_config_defaults' => (object) ['css_class' => '']],
            ]),
        ];
    }

    /**
     * @return array{type_key: string, label: string, version: string, block_template: array<string, mixed>, wizard_config: array<string, mixed>|null}
     */
    public static function resolve(string $entityKind, string $typeKey): array
    {
        $kind = strtolower(trim($entityKind));
        $type = trim($typeKey);

        if ($kind === 'collection') {
            return self::resolveCollection($type);
        }

        return self::resolvePage($type);
    }

    /**
     * @return list<string>
     */
    public static function optionKeys(string $entityKind): array
    {
        return strtolower(trim($entityKind)) === 'collection'
            ? self::collectionTypes()
            : self::pageTypes();
    }

    /**
     * @return array{type_key: string, label: string, version: string, block_template: array<string, mixed>, wizard_config: array<string, mixed>|null}
     */
    public static function resolveCollection(string $typeKey): array
    {
        $typeKey = strtolower(trim($typeKey));
        $presets = self::collectionPresets();
        foreach ($presets as $preset) {
            if ($preset['type_key'] === $typeKey) {
                return $preset;
            }
        }

        return self::resolveCollection('other');
    }

    /**
     * @return array{type_key: string, label: string, version: string, block_template: array<string, mixed>, wizard_config: null}
     */
    public static function resolvePage(string $typeKey): array
    {
        $typeKey = strtolower(trim($typeKey));
        $presets = self::pagePresets();
        foreach ($presets as $preset) {
            if ($preset['type_key'] === $typeKey) {
                return $preset;
            }
        }

        return self::resolvePage('generic');
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     * @param array<int, array<string, mixed>>|null $wizardConfig
     * @return array{type_key: string, label: string, version: string, block_template: array<string, mixed>, wizard_config: array<string, mixed>|null}
     */
    private static function collectionPreset(string $typeKey, array $blocks, ?array $wizardConfig): array
    {
        return self::preset('collection', $typeKey, $blocks, $wizardConfig);
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     * @return array{type_key: string, label: string, version: string, block_template: array<string, mixed>, wizard_config: null}
     */
    private static function pagePreset(string $typeKey, array $blocks): array
    {
        $normalizedBlocks = [];
        foreach (array_values($blocks) as $index => $block) {
            $block['sort_order'] = $index + 1;
            $normalizedBlocks[] = $block;
        }

        return [
            'type_key' => $typeKey,
            'label' => ucfirst(str_replace(['-', '_'], ' ', $typeKey)),
            'version' => '1.0',
            'block_template' => [
                'version' => '1.0',
                'blocks' => $normalizedBlocks,
            ],
            'wizard_config' => null,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     * @param array<int, array<string, mixed>>|null $wizardConfig
     * @return array{type_key: string, label: string, version: string, block_template: array<string, mixed>, wizard_config: array<string, mixed>|null}
     */
    private static function preset(string $kind, string $typeKey, array $blocks, ?array $wizardConfig): array
    {
        $normalizedBlocks = [];
        foreach (array_values($blocks) as $index => $block) {
            $block['sort_order'] = $index + 1;
            $normalizedBlocks[] = $block;
        }

        return [
            'type_key' => $typeKey,
            'label' => ucfirst(str_replace(['-', '_'], ' ', $typeKey)),
            'version' => '1.0',
            'block_template' => [
                'version' => '1.0',
                'blocks' => $normalizedBlocks,
            ],
            'wizard_config' => $kind === 'collection'
                ? [
                    'type' => $typeKey,
                    'steps' => $wizardConfig ?? [],
                ]
                : null,
        ];
    }
}
