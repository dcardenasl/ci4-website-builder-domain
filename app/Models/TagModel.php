<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\TagEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class TagModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_tags';
    protected $primaryKey = 'id';
    protected $returnType = TagEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = ['is_active'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at'];

    protected $validationRules = [
        'is_active' => 'permit_empty|boolean_like',
    ];
}
