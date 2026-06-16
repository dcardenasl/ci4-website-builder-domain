<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\LanguageEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class LanguageModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_languages';
    protected $primaryKey = 'id';
    protected $returnType = LanguageEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'code',
        'name',
        'native_name',
        'is_default',
        'is_active',
        'fallback_language_id',
        'sort_order',
    ];

    /** @var array<int, string> */
    protected array $searchableFields = ['code', 'name', 'native_name'];

    /** @var array<int, string> */
    protected array $filterableFields = ['is_active', 'is_default'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'sort_order', 'code', 'name'];

    protected $validationRules = [
        'code'                 => 'required|string|max_length[10]',
        'name'                 => 'required|string|max_length[50]',
        'native_name'          => 'required|string|max_length[50]',
        'is_default'           => 'permit_empty|boolean_like',
        'is_active'            => 'permit_empty|boolean_like',
        'fallback_language_id' => 'permit_empty|is_natural_no_zero',
        'sort_order'           => 'permit_empty|integer',
    ];
}
