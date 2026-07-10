<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Verifies signed preview links so ?preview=1 can only bypass the
 * published/workflow_status/scheduled_at gates when the caller holds
 * CMS_PREVIEW_SECRET (shared with the admin panel, never with the browser).
 *
 * Fails closed: any missing secret, missing/malformed params, or expired
 * link is treated as "not a valid preview" — the caller then falls back to
 * the normal published-only rules.
 */
class PreviewToken
{
    /**
     * $identifier identifies the exact resource the link was signed for —
     * an entry ID (entries resolve by slug regardless of workflow_status, so
     * an ID is known upfront), or "{lang}:{slug}" for pages (page slug
     * resolution itself is published-only, so there is no page ID to bind to
     * until the caller decides whether to bypass that filter — see
     * SlugRouter::resolve()'s $includeUnpublished parameter).
     */
    public static function verify(string $type, string $identifier, ?string $expiresRaw, ?string $signatureRaw): bool
    {
        $secret = (string) env('CMS_PREVIEW_SECRET', '');
        if ($secret === '') {
            return false;
        }

        if ($expiresRaw === null || $expiresRaw === '' || $signatureRaw === null || $signatureRaw === '') {
            return false;
        }

        if (! ctype_digit($expiresRaw)) {
            return false;
        }

        $expires = (int) $expiresRaw;
        if ($expires < time()) {
            return false;
        }

        $expected = hash_hmac('sha256', self::canonical($type, $identifier, $expires), $secret);

        return hash_equals($expected, $signatureRaw);
    }

    private static function canonical(string $type, string $identifier, int $expires): string
    {
        return $type . ':' . $identifier . ':' . $expires;
    }
}
