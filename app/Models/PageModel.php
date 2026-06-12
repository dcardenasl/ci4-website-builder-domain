<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\PageEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class PageModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_pages';
    protected $primaryKey = 'id';
    protected $returnType = PageEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['parent_id', 'page_type', 'status', 'published_at', 'scheduled_at', 'sort_order', 'sitemap_priority', 'sitemap_changefreq', 'is_in_sitemap'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at'];

    protected $validationRules = [
        'parent_id' => 'permit_empty|integer',
        'page_type' => 'required|string|max_length[255]',
        'status' => 'required|string|max_length[255]',
        'published_at' => 'permit_empty|valid_date',
        'scheduled_at' => 'permit_empty|valid_date',
        'sort_order' => 'required|integer',
        'sitemap_priority' => 'permit_empty|decimal',
        'sitemap_changefreq' => 'permit_empty|string|max_length[255]',
        'is_in_sitemap' => 'required|boolean_like',
    ];
}
