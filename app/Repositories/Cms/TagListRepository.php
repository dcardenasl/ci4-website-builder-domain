<?php

declare(strict_types=1);

namespace App\Repositories\Cms;

use App\Interfaces\Cms\AdminListProjectionRepositoryInterface;
use App\Models\TagModel;

/** SQL read model for the administrative tag table. */
final class TagListRepository extends AbstractAdminListProjectionRepository implements AdminListProjectionRepositoryInterface
{
    public function __construct(TagModel $model, \CodeIgniter\Database\BaseConnection $db)
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
        $search = trim((string) ($criteria['search'] ?? ''));

        if ($search !== '') {
            $where[] = <<<'SQL'
EXISTS (
    SELECT 1
    FROM cms_tag_translations ts
    WHERE ts.tag_id = t.id
      AND (ts.name LIKE ? OR ts.slug LIKE ?)
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle);
        }

        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sortExpressions = [
            'name'        => ['sort_name ASC, t.id ASC', 'f.sort_name ASC, f.id ASC'],
            '-name'       => ['sort_name DESC, t.id DESC', 'f.sort_name DESC, f.id DESC'],
            'created_at'  => ['t.created_at ASC, t.id ASC', 'f.created_at ASC, f.id ASC'],
            '-created_at' => ['t.created_at DESC, t.id DESC', 'f.created_at DESC, f.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sortExpressions[$sort] ?? $sortExpressions['-created_at'];
        $whereSql = $where === [] ? '1 = 1' : implode("\n      AND ", $where);

        $sql = <<<SQL
WITH filtered_tags AS (
    SELECT
        t.id,
        t.is_active,
        t.created_at,
        t.updated_at,
        COALESCE((SELECT ts.name FROM cms_tag_translations ts WHERE ts.tag_id = t.id ORDER BY ts.language_id ASC, ts.id ASC LIMIT 1), '') AS name,
        COALESCE((SELECT ts.slug FROM cms_tag_translations ts WHERE ts.tag_id = t.id ORDER BY ts.language_id ASC, ts.id ASC LIMIT 1), '') AS slug,
        COALESCE((SELECT ts.name FROM cms_tag_translations ts WHERE ts.tag_id = t.id ORDER BY ts.language_id ASC, ts.id ASC LIMIT 1), '') AS sort_name,
        COUNT(*) OVER () AS total_items
    FROM cms_tags t
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT
    f.id,
    f.is_active,
    f.created_at,
    f.updated_at,
    f.name,
    f.slug,
    GROUP_CONCAT(
        CONCAT(ts.language_id, ':', COALESCE(HEX(ts.name), ''), ':', COALESCE(HEX(ts.slug), ''))
        ORDER BY ts.language_id ASC, ts.id ASC SEPARATOR '|'
    ) AS translations_data,
    MAX(f.total_items) AS total_items
FROM filtered_tags f
LEFT JOIN cms_tag_translations ts ON ts.tag_id = f.id
GROUP BY f.id, f.is_active, f.created_at, f.updated_at, f.name, f.slug, f.sort_name
ORDER BY {$outerOrder}
SQL;

        return $this->executeProjection($sql, $binds, $page, $perPage, 'Unable to execute the tag list projection.');
    }
}
