<?php

declare(strict_types=1);

namespace App\Repositories\Cms;

use App\Interfaces\Cms\AdminListProjectionRepositoryInterface;
use App\Models\CollectionModel;

/** SQL read model for the administrative collection table. */
final class CollectionListRepository extends AbstractAdminListProjectionRepository implements AdminListProjectionRepositoryInterface
{
    public function __construct(CollectionModel $model, \CodeIgniter\Database\BaseConnection $db)
    {
        parent::__construct($model, $db);
    }

    public function paginateAdminList(array $criteria, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = min(1000, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['1 = 1'];
        $binds = [];

        if (($criteria['resource_scope_superadmin'] ?? null) !== true
            && array_key_exists('resource_scope_user_id', $criteria)
        ) {
            $where[] = <<<'SQL'
EXISTS (
    SELECT 1 FROM cms_resource_access ra_collection
    WHERE ra_collection.resource_type = 'collection'
      AND ra_collection.resource_id = c.id
      AND ra_collection.user_id = ?
      AND ra_collection.access_level IN ('read', 'write', 'admin')
)
SQL;
            $binds[] = (int) $criteria['resource_scope_user_id'];
        }
        $search = trim((string) ($criteria['search'] ?? ''));

        if ($search !== '') {
            $where[] = <<<'SQL'
(
    c.collection_key LIKE ?
    OR c.collection_type LIKE ?
    OR EXISTS (
        SELECT 1 FROM cms_collection_translations ct_search
        WHERE ct_search.collection_id = c.id
          AND (ct_search.name LIKE ? OR ct_search.slug LIKE ?)
    )
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle, $needle, $needle);
        }

        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sortExpressions = [
            'name'            => ['sort_name ASC, c.id ASC', 'f.sort_name ASC, f.id ASC'],
            '-name'           => ['sort_name DESC, c.id DESC', 'f.sort_name DESC, f.id DESC'],
            'collection_key'  => ['c.collection_key ASC, c.id ASC', 'f.collection_key ASC, f.id ASC'],
            '-collection_key' => ['c.collection_key DESC, c.id DESC', 'f.collection_key DESC, f.id DESC'],
            'created_at'      => ['c.created_at ASC, c.id ASC', 'f.created_at ASC, f.id ASC'],
            '-created_at'     => ['c.created_at DESC, c.id DESC', 'f.created_at DESC, f.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sortExpressions[$sort] ?? $sortExpressions['-created_at'];
        $whereSql = implode("\n      AND ", $where);

        $sql = <<<SQL
WITH filtered_collections AS (
    SELECT
        c.id,
        c.collection_key,
        c.collection_type,
        c.is_active,
        c.requires_approval,
        c.enables_categories,
        c.enables_tags,
        c.default_sitemap_priority,
        c.default_changefreq,
        c.block_template,
        c.wizard_config,
        c.sort_order,
        c.created_at,
        c.updated_at,
        COALESCE((SELECT ct.name FROM cms_collection_translations ct WHERE ct.collection_id = c.id ORDER BY ct.language_id ASC, ct.id ASC LIMIT 1), '') AS sort_name,
        COUNT(*) OVER () AS total_items
    FROM cms_collections c
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT
    f.id,
    f.collection_key,
    f.collection_type,
    f.is_active,
    f.requires_approval,
    f.enables_categories,
    f.enables_tags,
    f.default_sitemap_priority,
    f.default_changefreq,
    f.block_template,
    f.wizard_config,
    f.sort_order,
    f.created_at,
    f.updated_at,
    GROUP_CONCAT(
        CONCAT(
            ct.language_id,
            ':',
            COALESCE(HEX(ct.name), ''),
            ':',
            COALESCE(HEX(ct.slug), '')
        ) ORDER BY ct.language_id ASC, ct.id ASC SEPARATOR '|'
    ) AS translations_data,
    MAX(f.total_items) AS total_items
FROM filtered_collections f
LEFT JOIN cms_collection_translations ct ON ct.collection_id = f.id
GROUP BY f.id, f.collection_key, f.collection_type, f.is_active,
         f.requires_approval, f.enables_categories, f.enables_tags,
         f.default_sitemap_priority, f.default_changefreq, f.block_template,
         f.wizard_config, f.sort_order, f.created_at, f.updated_at, f.sort_name
ORDER BY {$outerOrder}
SQL;

        return $this->executeProjection($sql, $binds, $page, $perPage, 'Unable to execute the collection list projection.');
    }
}
