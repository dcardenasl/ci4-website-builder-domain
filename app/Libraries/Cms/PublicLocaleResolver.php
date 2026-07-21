<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseConnection;

/**
 * Resolves the CMS content locale for a public request from its raw
 * Accept-Language header — the API's static framework locale list must not
 * decide CMS content language, and every public endpoint used to reimplement
 * this same header-parsing + active/default-language fallback independently
 * (PublicMenuController, PublicSettingController).
 *
 * Fallback priority: requested language if active in the CMS -> CMS default
 * language -> framework's App.defaultLocale.
 */
class PublicLocaleResolver
{
    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    public function __construct(private readonly BaseConnection $db)
    {
    }

    public function resolve(?string $acceptLanguageHeader): string
    {
        $header = trim((string) $acceptLanguageHeader);
        $locale = strtolower(trim((string) explode(',', $header)[0]));
        $locale = preg_replace('/[^a-z0-9-].*$/', '', $locale) ?? '';

        if ($locale !== '') {
            $activeResult = $this->db->table('cms_languages')
                ->where('is_active', 1)
                ->where('code', $locale)
                ->get();
            $active = $activeResult !== false ? $activeResult->getRow() : null;

            if ($active !== null) {
                return $locale;
            }
        }

        $defaultResult = $this->db->table('cms_languages')
            ->where('is_active', 1)
            ->where('is_default', 1)
            ->get();
        $default = $defaultResult !== false ? $defaultResult->getRow() : null;

        return $default !== null
            ? (string) $default->code
            : (string) config('App')->defaultLocale;
    }
}
