<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\MenuEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class MenuModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_menus';
    protected $primaryKey = 'id';
    protected $returnType = MenuEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['menu_key', 'location', 'is_active'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at'];

    protected $validationRules = [
        'menu_key' => 'required|string|max_length[50]',
        'location' => 'required|string|max_length[50]',
        'is_active' => 'permit_empty|boolean_like',
    ];
}
