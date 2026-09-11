<?php

declare(strict_types=1);

namespace App\Repositories\Cms;

use App\Interfaces\Cms\EntryListRepositoryInterface;
use App\Models\EntryModel;

/**
 * SQL projection for the administrative entry table.
 *
 * The database returns the page, its translations, collection key and total
 * count in one statement. The service must not hydrate this projection with
 * per-entry HTTP calls or follow-up translation queries.
 */
final class EntryListRepository extends AbstractAdminListProjectionRepository implements EntryListRepositoryInterface
{
    public function __construct(EntryModel $model, \CodeIgniter\Database\BaseConnection $db)
    {
        parent::__construct($model, $db);
    }

    public function paginateAdminList(array $criteria, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = min(1000, max(1, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ['e.deleted_at IS NULL'];
        $binds = [];

        if (($criteria['resource_scope_superadmin'] ?? null) !== true
            && array_key_exists('resource_scope_user_id', $criteria)
        ) {
            $where[] = <<<'SQL'
(
    EXISTS (
        SELECT 1 FROM cms_resource_access ra_entry
        WHERE ra_entry.resource_type = 'entry'
          AND ra_entry.resource_id = e.id
          AND ra_entry.user_id = ?
          AND ra_entry.access_level IN ('read', 'write', 'admin')
    )
    OR EXISTS (
        SELECT 1 FROM cms_resource_access ra_collection
        WHERE ra_collection.resource_type = 'collection'
          AND ra_collection.resource_id = e.collection_id
          AND ra_collection.user_id = ?
          AND ra_collection.access_level IN ('read', 'write', 'admin')
    )
)
SQL;
            $userId = (int) $criteria['resource_scope_user_id'];
            $binds[] = $userId;
            $binds[] = $userId;
        }
        $filter = isset($criteria['filter']) && is_array($criteria['filter']) ? $criteria['filter'] : [];
        $collectionId = $filter['collection_id'] ?? ($criteria['collection_id'] ?? null);

        if ($collectionId !== null && $collectionId !== '' && is_numeric($collectionId)) {
            $where[] = 'e.collection_id = ?';
            $binds[] = (int) $collectionId;
        }

        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $where[] = <<<'SQL'
EXISTS (
    SELECT 1
    FROM cms_entry_translations et_search
    WHERE et_search.entry_id = e.id
      AND (
          et_search.title LIKE ?
          OR et_search.slug LIKE ?
          OR c.collection_key LIKE ?
      )
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle, $needle);
        }

        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sortExpressions = [
            'created_at'  => ['e.created_at ASC, e.id ASC', 'f.created_at ASC, f.id ASC'],
            '-created_at' => ['e.created_at DESC, e.id DESC', 'f.created_at DESC, f.id DESC'],
            'id'          => ['e.id ASC', 'f.id ASC'],
            '-id'         => ['e.id DESC', 'f.id DESC'],
            'sort_order'  => ['e.sort_order ASC, e.id ASC', 'f.sort_order ASC, f.id ASC'],
            '-sort_order' => ['e.sort_order DESC, e.id DESC', 'f.sort_order DESC, f.id DESC'],
            'name'        => ['sort_title ASC, e.id ASC', 'f.sort_title ASC, f.id ASC'],
            '-name'       => ['sort_title DESC, e.id DESC', 'f.sort_title DESC, f.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sortExpressions[$sort] ?? $sortExpressions['-created_at'];
        $whereSql = implode("\n      AND ", $where);

        $sql = <<<SQL
WITH filtered_entries AS (
    SELECT
        e.id,
        e.collection_id,
        e.author_id,
        e.workflow_status,
        e.published_at,
        e.scheduled_at,
        e.is_featured,
        e.view_count,
        e.sort_order,
        e.sitemap_priority,
        e.sitemap_changefreq,
        e.is_in_sitemap,
        e.created_at,
        e.updated_at,
        c.collection_key,
        COALESCE((SELECT et_primary.title FROM cms_entry_translations et_primary WHERE et_primary.entry_id = e.id ORDER BY et_primary.language_id ASC, et_primary.id ASC LIMIT 1), '') AS title,
        COALESCE((SELECT et_primary.slug FROM cms_entry_translations et_primary WHERE et_primary.entry_id = e.id ORDER BY et_primary.language_id ASC, et_primary.id ASC LIMIT 1), '') AS slug,
        COALESCE((SELECT et_primary.title FROM cms_entry_translations et_primary WHERE et_primary.entry_id = e.id ORDER BY et_primary.language_id ASC, et_primary.id ASC LIMIT 1), '') AS sort_title,
        COUNT(*) OVER () AS total_items
    FROM cms_entries e
    LEFT JOIN cms_collections c ON c.id = e.collection_id
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT
    f.id,
    f.collection_id,
    f.author_id,
    f.workflow_status,
    f.published_at,
    f.scheduled_at,
    f.is_featured,
    f.view_count,
    f.sort_order,
    f.sitemap_priority,
    f.sitemap_changefreq,
    f.is_in_sitemap,
    f.created_at,
    f.updated_at,
    f.collection_key,
    f.title,
    f.slug,
    GROUP_CONCAT(
        CONCAT(et.language_id, ':', HEX(et.title), ':', HEX(et.slug))
        ORDER BY et.language_id ASC, et.id ASC SEPARATOR '|'
    ) AS translations_data,
    MAX(f.total_items) AS total_items
FROM filtered_entries f
LEFT JOIN cms_entry_translations et ON et.entry_id = f.id
GROUP BY f.id, f.collection_id, f.author_id, f.workflow_status,
         f.published_at, f.scheduled_at, f.is_featured, f.view_count,
         f.sort_order, f.sitemap_priority, f.sitemap_changefreq,
         f.is_in_sitemap, f.created_at, f.updated_at, f.collection_key,
         f.title, f.slug, f.sort_title
ORDER BY {$outerOrder}
SQL;

        return $this->executeProjection($sql, $binds, $page, $perPage, 'Unable to execute the entry list projection.');
    }
}
