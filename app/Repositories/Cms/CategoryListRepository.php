<?php

declare(strict_types=1);

namespace App\Repositories\Cms;

use App\Interfaces\Cms\AdminListProjectionRepositoryInterface;
use App\Models\CategoryModel;

/** SQL read model for the administrative category table. */
final class CategoryListRepository extends AbstractAdminListProjectionRepository implements AdminListProjectionRepositoryInterface
{
    public function __construct(CategoryModel $model, \CodeIgniter\Database\BaseConnection $db)
    {
        parent::__construct($model, $db);
    }

    public function paginateAdminList(array $criteria, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = min(1000, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = [];
        $binds = [];

        $collectionId = $this->criteriaValue($criteria, 'collection_id');
        if ($collectionId !== null && $collectionId !== '' && is_numeric($collectionId)) {
            $where[] = 'cat.collection_id = ?';
            $binds[] = (int) $collectionId;
        }

        $parentId = $this->criteriaValue($criteria, 'parent_id');
        if ($parentId !== null && $parentId !== '' && is_numeric($parentId)) {
            $where[] = 'cat.parent_id = ?';
            $binds[] = (int) $parentId;
        }

        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $where[] = <<<'SQL'
(
EXISTS (
    SELECT 1 FROM cms_category_translations ct_search
    WHERE ct_search.category_id = cat.id
      AND (ct_search.name LIKE ? OR ct_search.slug LIKE ?)
)
OR c.collection_key LIKE ?
OR EXISTS (
    SELECT 1 FROM cms_category_translations parent_search
    WHERE parent_search.category_id = cat.parent_id
      AND (parent_search.name LIKE ? OR parent_search.slug LIKE ?)
)
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle, $needle, $needle, $needle);
        }

        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sortExpressions = [
            'name'        => ['sort_name ASC, cat.id ASC', 'f.sort_name ASC, f.id ASC'],
            '-name'       => ['sort_name DESC, cat.id DESC', 'f.sort_name DESC, f.id DESC'],
            'created_at'  => ['cat.created_at ASC, cat.id ASC', 'f.created_at ASC, f.id ASC'],
            '-created_at' => ['cat.created_at DESC, cat.id DESC', 'f.created_at DESC, f.id DESC'],
            'parent_id'   => ['cat.parent_id ASC, cat.id ASC', 'f.parent_id ASC, f.id ASC'],
            '-parent_id'  => ['cat.parent_id DESC, cat.id DESC', 'f.parent_id DESC, f.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sortExpressions[$sort] ?? $sortExpressions['-created_at'];
        $whereSql = $where === [] ? '1 = 1' : implode("\n      AND ", $where);

        $sql = <<<SQL
WITH filtered_categories AS (
    SELECT
        cat.id,
        cat.collection_id,
        cat.parent_id,
        cat.sort_order,
        cat.is_active,
        cat.created_at,
        cat.updated_at,
        COALESCE((SELECT ct.name FROM cms_category_translations ct WHERE ct.category_id = cat.id ORDER BY ct.language_id ASC, ct.id ASC LIMIT 1), '') AS name,
        COALESCE((SELECT ct.slug FROM cms_category_translations ct WHERE ct.category_id = cat.id ORDER BY ct.language_id ASC, ct.id ASC LIMIT 1), '') AS slug,
        COALESCE((SELECT ct.name FROM cms_category_translations ct WHERE ct.category_id = cat.id ORDER BY ct.language_id ASC, ct.id ASC LIMIT 1), '') AS sort_name,
        COALESCE((SELECT ct.name FROM cms_collection_translations ct WHERE ct.collection_id = cat.collection_id ORDER BY ct.language_id ASC, ct.id ASC LIMIT 1), c.collection_key) AS collection_name,
        COALESCE((SELECT pt.name FROM cms_category_translations pt WHERE pt.category_id = cat.parent_id ORDER BY pt.language_id ASC, pt.id ASC LIMIT 1), '') AS parent_label,
        COUNT(*) OVER () AS total_items
    FROM cms_categories cat
    LEFT JOIN cms_collections c ON c.id = cat.collection_id
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT
    f.id,
    f.collection_id,
    f.parent_id,
    f.sort_order,
    f.is_active,
    f.created_at,
    f.updated_at,
    f.name,
    f.slug,
    f.collection_name,
    NULLIF(f.parent_label, '') AS parent_label,
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
FROM filtered_categories f
LEFT JOIN cms_category_translations ct ON ct.category_id = f.id
GROUP BY f.id, f.collection_id, f.parent_id, f.sort_order, f.is_active,
         f.created_at, f.updated_at, f.name, f.slug, f.collection_name,
         f.parent_label, f.sort_name
ORDER BY {$outerOrder}
SQL;

        return $this->executeProjection($sql, $binds, $page, $perPage, 'Unable to execute the category list projection.');
    }
}
