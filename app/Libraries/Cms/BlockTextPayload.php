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
    private const USAGE_COUNTER_CACHE_KEY = 'legacy_block_text_key_usage_count';

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
            self::recordUsage();
            break;
        }

        return $data;
    }

    /**
     * Cumulative count of writes that still arrived with a legacy key,
     * persisted outside the app log (TTL 0 = never expires). Unlike web's
     * read-side counter, this fires on *save*, not render — a more direct
     * signal for "is new/edited content still using legacy keys" than
     * counting render-time hits on the same stale rows repeatedly. Read via
     * `php spark legacy:block-text-report`. See DEBT-002 in root TASKS.md.
     *
     * @param 'read'|'increment'|'reset' $action
     */
    public static function usageCount(string $action = 'read'): int
    {
        $cache = \Config\Services::cache();

        if ($action === 'reset') {
            $cache->save(self::USAGE_COUNTER_CACHE_KEY, 0, 0);

            return 0;
        }

        if ($action === 'increment') {
            if ($cache->get(self::USAGE_COUNTER_CACHE_KEY) === null) {
                $cache->save(self::USAGE_COUNTER_CACHE_KEY, 0, 0);
            }
            $cache->increment(self::USAGE_COUNTER_CACHE_KEY);
        }

        $value = $cache->get(self::USAGE_COUNTER_CACHE_KEY);

        return is_int($value) ? $value : 0;
    }

    private static function recordUsage(): void
    {
        self::usageCount('increment');
    }
}
