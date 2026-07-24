<?php

declare(strict_types=1);

namespace App\Database\Seeds\Concerns;

/**
 * Single source of truth for the `block_template` + `wizard_config` a starter
 * collection ships with (news, portfolio).
 *
 * Before 2026-07-22, `NewsCollectionSeeder`/`PortfolioCollectionSeeder` each
 * hand-maintained their own copy of this array, and `WizardConfigSeeder` held
 * a third, independently-edited copy used only to repair already-seeded
 * collections. The two News copies drifted: the seeder still listed
 * page_header/hero_banner/cta/alert (landing-page blocks, never part of the
 * actual article template — the seeder's own sample entries were already
 * pruned down to rich_text+image, see `cleanupStaleEntryBlocks()`), so every
 * time `NewsCollectionSeeder` ran on its own it silently reset the
 * collection's `block_template` back to the stale 6-block set, and the
 * Wizard's "Agregar contenido" flow started asking editors to fill in blocks
 * that don't belong on a noticia. Portfolio had the identical unfixed
 * duplicate. Reading both the fresh-install seeders and the repair seeder
 * from here means there is exactly one array literal per collection type —
 * it cannot diverge again.
 */
final class CollectionBlockPresets
{
    /**
     * @return array{block_template: array<string, mixed>, wizard_config: array<string, mixed>}
     */
    public static function news(): array
    {
        return [
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
                    ['step_title' => 'Titular y resumen', 'step_hint' => 'Título visible para la noticia y una breve bajada informativa', 'fields' => [
                        ['key' => 'title', 'label' => 'Titular', 'type' => 'text', 'required' => true],
                        ['key' => 'excerpt', 'label' => 'Resumen', 'type' => 'textarea', 'required' => false],
                    ]],
                    ['step_title' => 'Imagen destacada', 'step_hint' => 'Portada de la noticia (biblioteca o URL)', 'fields' => [['key' => 'featured_image', 'label' => 'Imagen destacada', 'type' => 'image', 'required' => false]]],
                ],
            ],
        ];
    }

    /**
     * @return array{block_template: array<string, mixed>, wizard_config: array<string, mixed>}
     */
    public static function portfolio(): array
    {
        return [
            'block_template' => [
                'version' => '1.0',
                'blocks' => [
                    [
                        'block_key' => 'image',
                        'label' => 'Imagen del Proyecto',
                        'help_text' => 'Imagen principal del proyecto realizado',
                        'required' => true,
                        'locked' => false,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 1,
                    ],
                    [
                        'block_key' => 'rich_text',
                        'label' => 'Detalle del Proyecto',
                        'help_text' => 'Descripción detallada del caso de estudio',
                        'required' => false,
                        'locked' => false,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 2,
                    ],
                ],
            ],
            'wizard_config' => [
                'type' => 'portfolio',
                'steps' => [
                    ['step_title' => 'Proyecto y resumen', 'step_hint' => 'Nombre del proyecto y una breve descripción del trabajo realizado', 'fields' => [
                        ['key' => 'title', 'label' => 'Proyecto', 'type' => 'text', 'required' => true],
                        ['key' => 'excerpt', 'label' => 'Resumen', 'type' => 'textarea', 'required' => false],
                    ]],
                    ['step_title' => 'Imagen destacada', 'step_hint' => 'Portada del proyecto (biblioteca o URL)', 'fields' => [['key' => 'featured_image', 'label' => 'Imagen destacada', 'type' => 'image', 'required' => false]]],
                ],
            ],
        ];
    }

    /**
     * Collection-type key => preset, for repair passes that must cover every
     * starter collection type without hand-listing them at each call site.
     *
     * @return array<string, array{block_template: array<string, mixed>, wizard_config: array<string, mixed>}>
     */
    public static function all(): array
    {
        return [
            'news' => self::news(),
            'portfolio' => self::portfolio(),
        ];
    }

    /**
     * Collection-type key => collection_key, used by repair passes to also
     * match collections that haven't had `collection_type` backfilled yet.
     *
     * @return array<string, string>
     */
    public static function collectionKeys(): array
    {
        return [
            'news' => 'noticias',
            'portfolio' => 'portafolio',
        ];
    }
}
