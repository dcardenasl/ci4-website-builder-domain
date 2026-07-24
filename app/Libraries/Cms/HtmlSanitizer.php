<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitizes rich-text HTML content before persistence.
 *
 * Uses HTMLPurifier with a strict allowlist of safe elements and attributes.
 * Call clean() on any user-supplied HTML string (rich_text block content,
 * accordion content, rich text sections, etc.) before storing it in the database.
 *
 * The purifier instance is created once per process (singleton pattern).
 */
class HtmlSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(string $html): string
    {
        return self::getPurifier()->purify($html);
    }

    private static function getPurifier(): HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();

        // Cache directory for HTMLPurifier serializer
        $cacheDir = WRITEPATH . 'htmlpurifier';
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $config->set('Cache.SerializerPath', $cacheDir);

        // Enable ID attribute support
        $config->set('Attr.EnableID', true);

        // Allowed elements (rich text subset — no script/style/form/input)
        // Keep this list aligned with the editor toolbar and HTMLPurifier support.
        $config->set('HTML.Allowed', implode(',', [
            'div[id|class]',
            'p[id|class]', 'br',
            'b', 'strong', 'i', 'em', 'u', 's', 'small',
            'ul[id|class]', 'ol[id|class]', 'li[id|class]',
            'blockquote', 'pre', 'code',
            'h2[id|class]', 'h3[id|class]', 'h4[id|class]',
            'a[href|title|target|rel|id|class]',
            'img[src|alt|width|height|id|class]',
            'hr',
        ]));

        // Only http, https and mailto protocols in href/src
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('URI.SafeIframeRegexp', null);

        // Force rel="noopener noreferrer" on external links
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.TargetNoreferrer', true);
        $config->set('HTML.TargetNoopener', true);

        self::$purifier = new HTMLPurifier($config);

        return self::$purifier;
    }
}
