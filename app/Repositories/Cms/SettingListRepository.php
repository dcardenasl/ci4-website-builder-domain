<?php

declare(strict_types=1);

namespace App\Repositories\Cms;

use App\Interfaces\Cms\AdminListProjectionRepositoryInterface;
use App\Models\SettingModel;

/** SQL read model for the administrative setting table. */
final class SettingListRepository extends AbstractAdminListProjectionRepository implements AdminListProjectionRepositoryInterface
{
    public function __construct(SettingModel $model, \CodeIgniter\Database\BaseConnection $db)
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

        $group = $this->criteriaValue($criteria, 'setting_group');
        if (is_string($group) && $group !== '') {
            $where[] = 's.setting_group = ?';
            $binds[] = $group;
        }

        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $where[] = <<<'SQL'
(
    s.setting_key LIKE ?
    OR s.setting_group LIKE ?
    OR s.description LIKE ?
    OR s.setting_value LIKE ?
    OR EXISTS (
        SELECT 1 FROM cms_setting_translations st_search
        WHERE st_search.setting_id = s.id
          AND (st_search.setting_value LIKE ? OR st_search.label LIKE ? OR st_search.help_text LIKE ?)
    )
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle, $needle, $needle, $needle, $needle, $needle);
        }

        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sortExpressions = [
            'setting_key'      => ['s.setting_key ASC, s.id ASC', 'f.setting_key ASC, f.id ASC'],
            '-setting_key'     => ['s.setting_key DESC, s.id DESC', 'f.setting_key DESC, f.id DESC'],
            'setting_value'    => ['s.setting_value ASC, s.id ASC', 'f.setting_value ASC, f.id ASC'],
            '-setting_value'   => ['s.setting_value DESC, s.id DESC', 'f.setting_value DESC, f.id DESC'],
            'setting_type'     => ['s.setting_type ASC, s.id ASC', 'f.setting_type ASC, f.id ASC'],
            '-setting_type'    => ['s.setting_type DESC, s.id DESC', 'f.setting_type DESC, f.id DESC'],
            'setting_group'    => ['s.setting_group ASC, s.id ASC', 'f.setting_group ASC, f.id ASC'],
            '-setting_group'   => ['s.setting_group DESC, s.id DESC', 'f.setting_group DESC, f.id DESC'],
            'is_translatable'  => ['s.is_translatable ASC, s.id ASC', 'f.is_translatable ASC, f.id ASC'],
            '-is_translatable' => ['s.is_translatable DESC, s.id DESC', 'f.is_translatable DESC, f.id DESC'],
            'created_at'       => ['s.created_at ASC, s.id ASC', 'f.created_at ASC, f.id ASC'],
            '-created_at'      => ['s.created_at DESC, s.id DESC', 'f.created_at DESC, f.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sortExpressions[$sort] ?? $sortExpressions['-created_at'];
        $whereSql = $where === [] ? '1 = 1' : implode("\n      AND ", $where);

        $sql = <<<SQL
WITH filtered_settings AS (
    SELECT
        s.id,
        s.setting_key,
        s.setting_value,
        s.setting_type,
        s.input_type,
        s.options_json,
        s.setting_group,
        s.is_translatable,
        s.is_required,
        s.is_readonly,
        s.sort_order,
        s.description,
        s.created_at,
        s.updated_at,
        COUNT(*) OVER () AS total_items
    FROM cms_settings s
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT
    f.id,
    f.setting_key,
    f.setting_value,
    f.setting_type,
    f.input_type,
    f.options_json,
    f.setting_group,
    f.is_translatable,
    f.is_required,
    f.is_readonly,
    f.sort_order,
    f.description,
    f.created_at,
    f.updated_at,
    GROUP_CONCAT(
        CONCAT(ts.language_id, ':', COALESCE(HEX(ts.setting_value), ''))
        ORDER BY ts.language_id ASC, ts.id ASC SEPARATOR '|'
    ) AS translations_data,
    MAX(f.total_items) AS total_items
FROM filtered_settings f
LEFT JOIN cms_setting_translations ts ON ts.setting_id = f.id
GROUP BY f.id, f.setting_key, f.setting_value, f.setting_type, f.input_type,
         f.options_json, f.setting_group, f.is_translatable, f.is_required,
         f.is_readonly, f.sort_order, f.description, f.created_at, f.updated_at
ORDER BY {$outerOrder}
SQL;

        return $this->executeProjection($sql, $binds, $page, $perPage, 'Unable to execute the setting list projection.');
    }
}
