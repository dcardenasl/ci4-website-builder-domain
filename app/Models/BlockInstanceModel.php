<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\BlockInstanceEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class BlockInstanceModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_block_instances';
    protected $primaryKey = 'id';
    protected $returnType = BlockInstanceEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = ['block_id', 'owner_type', 'owner_id', 'parent_instance_id', 'sort_order', 'column_index', 'is_active', 'block_config'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'owner_type', 'owner_id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'owner_type', 'owner_id', 'sort_order', 'column_index'];

    protected $validationRules = [
        'block_id' => 'required|is_natural_no_zero|is_not_unique[cms_content_blocks.id]',
        'owner_type' => 'required|string|in_list[page,entry]',
        'owner_id' => 'required|integer',
        'parent_instance_id' => 'permit_empty|is_natural_no_zero|is_not_unique[cms_block_instances.id]',
        'sort_order' => 'required|integer',
        'column_index' => 'permit_empty|integer',
        'is_active' => 'permit_empty|boolean_like',
        'block_config' => 'permit_empty',
    ];
}
