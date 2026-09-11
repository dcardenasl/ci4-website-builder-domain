<?php

declare(strict_types=1);

namespace Config;

use dcardenasl\Ci4ApiCore\Config\Localization as CoreLocalization;

/**
 * Consumer localization registry.
 *
 * The current CMS keeps its legacy translation tables while the generic
 * sidecar contracts are introduced incrementally. This registry documents
 * the first public-slug reference resource and is ready for future EAV
 * translation migrations without changing the core package.
 */
final class Localization extends CoreLocalization
{
    /** @var array<string, list<string>> */
    public array $translatableFields = [
        'collection' => [
            'name',
            'description',
            'listing_title',
            'listing_intro',
            'default_meta_title',
            'default_meta_description',
            'entry_cta_label',
        ],
    ];

    public string $legacyFallbackLocale = 'es';
}
