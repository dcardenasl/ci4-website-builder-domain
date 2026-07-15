<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Canonicalizes rich-text block payloads that may still arrive with legacy
 * field names. `content` is the canonical key; `body`, `html` and `text`
 * are legacy aliases kept only for backward compatibility with pre-2026-07
 * payloads (stored rows were normalized by the BackfillLegacyBlockContentKeys
 * migration). Once the legacy aliases stop appearing in the wild, delete this
 * class and its call sites to close the legacy cycle.
 */
final class BlockTextPayload
{
    /**
     * Normalize legacy rich-text payload keys to the canonical `content` key.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        $content = trim((string) ($data['content'] ?? ''));
        if ($content !== '') {
            return $data;
        }

        foreach (['body', 'html', 'text'] as $legacyKey) {
            $legacyValue = $data[$legacyKey] ?? '';
            if (! is_string($legacyValue) || trim($legacyValue) === '') {
                continue;
            }

            $data['content'] = $legacyValue;
            break;
        }

        return $data;
    }
}
