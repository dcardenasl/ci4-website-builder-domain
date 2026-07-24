<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\CategoryEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class CategoryModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_categories';
    protected $primaryKey = 'id';
    protected $returnType = CategoryEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = ['collection_id', 'parent_id', 'sort_order', 'is_active'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'collection_id', 'parent_id', 'is_active'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at'];

    protected $validationRules = [
        'collection_id' => 'required|integer',
        'parent_id' => 'permit_empty|integer',
        'sort_order' => 'permit_empty|integer',
        'is_active' => 'permit_empty|boolean_like',
    ];
}
