<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\RedirectEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class RedirectModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_redirects';
    protected $primaryKey = 'id';
    protected $returnType = RedirectEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = ['old_path', 'new_url', 'redirect_type', 'is_active', 'hit_count', 'note'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at'];

    protected $validationRules = [
        'old_path' => 'required|string|max_length[255]',
        'new_url' => 'required|string|max_length[255]',
        'redirect_type' => 'required|integer',
        'is_active' => 'permit_empty|boolean_like',
        'hit_count' => 'permit_empty|integer',
        'note' => 'permit_empty|string|max_length[255]',
    ];
}
