<?php

declare(strict_types=1);

namespace App\Repositories\Cms;

use App\Interfaces\Cms\AdminListProjectionRepositoryInterface;
use App\Models\MenuModel;

/** SQL read model for the administrative menu table. */
final class MenuListRepository extends AbstractAdminListProjectionRepository implements AdminListProjectionRepositoryInterface
{
    public function __construct(MenuModel $model, \CodeIgniter\Database\BaseConnection $db)
    {
        parent::__construct($model, $db);
    }

    public function paginateAdminList(array $criteria, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = min(1000, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['m.deleted_at IS NULL'];
        $binds = [];
        $search = trim((string) ($criteria['search'] ?? ''));

        if ($search !== '') {
            $where[] = <<<'SQL'
(
    m.menu_key LIKE ?
    OR m.location LIKE ?
    OR EXISTS (
        SELECT 1 FROM cms_menu_translations mt_search
        WHERE mt_search.menu_id = m.id
          AND mt_search.name LIKE ?
    )
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle, $needle);
        }

        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sortExpressions = [
            'menu_key'    => ['m.menu_key ASC, m.id ASC', 'f.menu_key ASC, f.id ASC'],
            '-menu_key'   => ['m.menu_key DESC, m.id DESC', 'f.menu_key DESC, f.id DESC'],
            'created_at'  => ['m.created_at ASC, m.id ASC', 'f.created_at ASC, f.id ASC'],
            '-created_at' => ['m.created_at DESC, m.id DESC', 'f.created_at DESC, f.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sortExpressions[$sort] ?? $sortExpressions['-created_at'];
        $whereSql = implode("\n      AND ", $where);

        $sql = <<<SQL
WITH filtered_menus AS (
    SELECT
        m.id,
        m.menu_key,
        m.location,
        m.is_active,
        m.created_at,
        m.updated_at,
        (SELECT COUNT(*) FROM cms_menu_items mi WHERE mi.menu_id = m.id) AS items_count,
        COUNT(*) OVER () AS total_items
    FROM cms_menus m
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT
    f.id,
    f.menu_key,
    f.location,
    f.is_active,
    f.items_count,
    f.created_at,
    f.updated_at,
    GROUP_CONCAT(
        CONCAT(mt.language_id, ':', COALESCE(HEX(mt.name), ''))
        ORDER BY mt.language_id ASC, mt.id ASC SEPARATOR '|'
    ) AS translations_data,
    MAX(f.total_items) AS total_items
FROM filtered_menus f
LEFT JOIN cms_menu_translations mt ON mt.menu_id = f.id
GROUP BY f.id, f.menu_key, f.location, f.is_active, f.items_count, f.created_at, f.updated_at
ORDER BY {$outerOrder}
SQL;

        return $this->executeProjection($sql, $binds, $page, $perPage, 'Unable to execute the menu list projection.');
    }
}
