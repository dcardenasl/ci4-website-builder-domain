<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Fixed catalog of "native" entry-level fields the Wizard's step editor is
 * allowed to arrange into `wizard_config.steps`.
 *
 * Deliberately NOT open to arbitrary keys. A wizard_config.steps field key
 * outside this catalog only ever reaches real storage by accident: any extra
 * key ends up in the entry's transient `wizard_extra` JSON, which
 * `EntryBlockTemplateInitializer::extractBlockDataFromWizardExtra()` uses only
 * to auto-fill a block's schema field when the key happens to match it by
 * name — anything that doesn't match sits inert forever. This catalog is the
 * list of keys that always land somewhere real (an entry or entry-translation
 * column), so the step editor can offer them without risking dead data.
 */
final class WizardStepFieldCatalog
{
    public const ANCHOR_KEY = 'title';

    /**
     * key => expected wizard_config.steps[].fields[].type
     *
     * @var array<string, string>
     */
    public const ALLOWED_KEYS = [
        'title'            => 'text',
        'excerpt'          => 'textarea',
        'featured_image'   => 'image',
        'meta_title'       => 'text',
        'meta_description' => 'textarea',
        'og_image'         => 'image',
    ];

    public static function isAllowedKey(string $key): bool
    {
        return array_key_exists($key, self::ALLOWED_KEYS);
    }

    public static function expectedType(string $key): ?string
    {
        return self::ALLOWED_KEYS[$key] ?? null;
    }
}
