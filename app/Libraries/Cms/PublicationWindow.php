<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * Single definition of "this content is live right now".
 *
 * Publication is a window, not a flag: a row is public only when its workflow
 * status says `published` AND the window has opened — `published_at` in the
 * past (or unset) and `scheduled_at` in the past (or unset).
 *
 * The rule already existed, but only for entries, inlined twice in
 * {@see \App\Services\Cms\PublicEntryReader}. Pages gated on status alone, so a
 * page marked published with a future `published_at` stayed reachable by direct
 * URL — `published_at` was written by `ScheduledPublishingJob` but never read by
 * any access-control path. Both resources now share this class so the two
 * definitions cannot drift apart again.
 *
 * Deliberately NOT applied to structural lookups that resolve a collection's
 * URL prefix ({@see \App\Services\Cms\PublicCollectionReader::resolveCollectionIndexPage}
 * and {@see \App\Services\Cms\MenuItemService::getCollectionPrefix}): those answer
 * "what is the canonical prefix for this collection", not "may this be served".
 * Making prefix construction time-dependent would flip already-published entry
 * URLs to the fallback slug while an index page waits for its window, and back
 * again afterwards.
 */
final class PublicationWindow
{
    /** Status column for `cms_pages`. */
    public const PAGE_STATUS = 'status';

    /** Status column for `cms_entries`. */
    public const ENTRY_STATUS = 'workflow_status';

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Constrain a query to rows whose publication window is open.
     *
     * @param string      $statusColumn Status column, qualified when the query joins.
     * @param string|null $tableAlias   Prefix for the date columns; null uses bare names.
     * @param string|null $now          Injectable clock for tests; defaults to the current time.
     */
    public static function apply(
        BaseBuilder|Model $query,
        string $statusColumn = self::PAGE_STATUS,
        ?string $tableAlias = null,
        ?string $now = null,
    ): void {
        $now    = $now ?? self::now();
        $prefix = $tableAlias !== null && $tableAlias !== '' ? $tableAlias . '.' : '';

        $query->where($statusColumn, 'published')
            ->groupStart()
                ->where($prefix . 'published_at IS NULL')
                ->orWhere($prefix . 'published_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where($prefix . 'scheduled_at IS NULL')
                ->orWhere($prefix . 'scheduled_at <=', $now)
            ->groupEnd();
    }

    /**
     * Row-level equivalent of {@see apply()}, for a row already loaded.
     *
     * Both date columns are cast to `string` by the entities, so lexicographic
     * comparison of `Y-m-d H:i:s` matches what the database does in {@see apply()}.
     */
    public static function isOpen(
        ?string $status,
        ?string $publishedAt,
        ?string $scheduledAt,
        ?string $now = null,
    ): bool {
        if ($status !== 'published') {
            return false;
        }

        $now = $now ?? self::now();

        return self::reached($publishedAt, $now) && self::reached($scheduledAt, $now);
    }

    /** An unset timestamp means "no constraint", matching the `IS NULL` branch in {@see apply()}. */
    private static function reached(?string $timestamp, string $now): bool
    {
        if ($timestamp === null || trim($timestamp) === '') {
            return true;
        }

        return $timestamp <= $now;
    }
}
