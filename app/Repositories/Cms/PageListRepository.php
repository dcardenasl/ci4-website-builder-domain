<?php

declare(strict_types=1);

namespace App\Repositories\Cms;

use App\Interfaces\Cms\AdminListProjectionRepositoryInterface;
use App\Models\PageModel;

/** SQL read model for the administrative page table. */
final class PageListRepository extends AbstractAdminListProjectionRepository implements AdminListProjectionRepositoryInterface
{
    public function __construct(PageModel $model, \CodeIgniter\Database\BaseConnection $db)
    {
        parent::__construct($model, $db);
    }

    public function paginateAdminList(array $criteria, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = min(1000, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['p.deleted_at IS NULL'];
        $binds = [];

        $parentId = $this->criteriaValue($criteria, 'parent_id');
        if ($parentId !== null && $parentId !== '' && is_numeric($parentId)) {
            $where[] = 'p.parent_id = ?';
            $binds[] = (int) $parentId;
        }

        foreach (['status', 'page_type'] as $filter) {
            $value = $this->criteriaValue($criteria, $filter);
            if (is_string($value) && $value !== '') {
                $where[] = 'p.' . $filter . ' = ?';
                $binds[] = $value;
            }
        }

        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $where[] = <<<'SQL'
(
    p.page_type LIKE ?
    OR p.status LIKE ?
    OR EXISTS (
        SELECT 1 FROM cms_page_translations pt_search
        WHERE pt_search.page_id = p.id
          AND (pt_search.title LIKE ? OR pt_search.slug LIKE ? OR pt_search.excerpt LIKE ?)
    )
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle, $needle, $needle, $needle);
        }

        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sortExpressions = [
            'name'           => ['sort_title ASC, p.id ASC', 'f.sort_title ASC, f.id ASC'],
            '-name'          => ['sort_title DESC, p.id DESC', 'f.sort_title DESC, f.id DESC'],
            'page_type'      => ['p.page_type ASC, p.id ASC', 'f.page_type ASC, f.id ASC'],
            '-page_type'     => ['p.page_type DESC, p.id DESC', 'f.page_type DESC, f.id DESC'],
            'status'         => ['p.status ASC, p.id ASC', 'f.status ASC, f.id ASC'],
            '-status'        => ['p.status DESC, p.id DESC', 'f.status DESC, f.id DESC'],
            'parent_id'      => ['p.parent_id ASC, p.id ASC', 'f.parent_id ASC, f.id ASC'],
            '-parent_id'     => ['p.parent_id DESC, p.id DESC', 'f.parent_id DESC, f.id DESC'],
            'is_in_sitemap'  => ['p.is_in_sitemap ASC, p.id ASC', 'f.is_in_sitemap ASC, f.id ASC'],
            '-is_in_sitemap' => ['p.is_in_sitemap DESC, p.id DESC', 'f.is_in_sitemap DESC, f.id DESC'],
            'created_at'     => ['p.created_at ASC, p.id ASC', 'f.created_at ASC, f.id ASC'],
            '-created_at'    => ['p.created_at DESC, p.id DESC', 'f.created_at DESC, f.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sortExpressions[$sort] ?? $sortExpressions['-created_at'];
        $whereSql = implode("\n      AND ", $where);

        $sql = <<<SQL
WITH filtered_pages AS (
    SELECT
        p.id,
        p.parent_id,
        p.collection_id,
        p.page_type,
        p.status,
        p.published_at,
        p.scheduled_at,
        p.sort_order,
        p.sitemap_priority,
        p.sitemap_changefreq,
        p.is_in_sitemap,
        p.created_at,
        p.updated_at,
        COALESCE((SELECT pt.title FROM cms_page_translations pt WHERE pt.page_id = p.id ORDER BY pt.language_id ASC, pt.id ASC LIMIT 1), '') AS sort_title,
        COUNT(*) OVER () AS total_items
    FROM cms_pages p
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT
    f.id,
    f.parent_id,
    f.collection_id,
    f.page_type,
    f.status,
    f.published_at,
    f.scheduled_at,
    f.sort_order,
    f.sitemap_priority,
    f.sitemap_changefreq,
    f.is_in_sitemap,
    f.created_at,
    f.updated_at,
    GROUP_CONCAT(
        CONCAT(
            pt.language_id,
            ':',
            COALESCE(HEX(pt.title), ''),
            ':',
            COALESCE(HEX(pt.slug), '')
        ) ORDER BY pt.language_id ASC, pt.id ASC SEPARATOR '|'
    ) AS translations_data,
    MAX(f.total_items) AS total_items
FROM filtered_pages f
LEFT JOIN cms_page_translations pt ON pt.page_id = f.id
GROUP BY f.id, f.parent_id, f.collection_id, f.page_type, f.status,
         f.published_at, f.scheduled_at, f.sort_order, f.sitemap_priority,
         f.sitemap_changefreq, f.is_in_sitemap, f.created_at, f.updated_at,
         f.sort_title
ORDER BY {$outerOrder}
SQL;

        return $this->executeProjection($sql, $binds, $page, $perPage, 'Unable to execute the page list projection.');
    }
}
