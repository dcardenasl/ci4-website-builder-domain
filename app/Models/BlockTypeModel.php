<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\BlockTypeEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class BlockTypeModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_content_blocks';
    protected $primaryKey = 'id';
    protected $returnType = BlockTypeEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = ['block_key', 'name', 'description', 'category', 'icon', 'schema_definition', 'supports_pages', 'supports_entries', 'is_container', 'is_active', 'sort_order'];

    /** @var array<int, string> */
    protected array $searchableFields = ['block_key', 'name'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'category', 'is_active'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'block_key', 'name', 'category', 'is_active'];

    protected $validationRules = [
        'block_key' => 'required|string|max_length[50]',
        'name' => 'required|string|max_length[100]',
        'description' => 'permit_empty|string',
        'category' => 'required|string|max_length[50]',
        'icon' => 'permit_empty|string|max_length[50]',
        'schema_definition' => 'required',
        'supports_pages' => 'permit_empty|boolean_like',
        'supports_entries' => 'permit_empty|boolean_like',
        'is_container' => 'permit_empty|boolean_like',
        'is_active' => 'permit_empty|boolean_like',
        'sort_order' => 'required|integer',
    ];
}
