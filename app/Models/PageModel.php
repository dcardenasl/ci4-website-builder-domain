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

    protected $allowedFields = ['parent_id', 'collection_id', 'page_type', 'status', 'published_at', 'scheduled_at', 'sort_order', 'sitemap_priority', 'sitemap_changefreq', 'is_in_sitemap'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at'];

    protected $validationRules = [
        'parent_id' => 'permit_empty|is_natural_no_zero',
        'collection_id' => 'permit_empty|is_natural_no_zero',
        'page_type' => 'required|in_list[home,generic,contact,privacy,terms,404,500,maintenance,collection_index]',
        'status' => 'required|in_list[draft,published,archived]',
        'published_at' => 'permit_empty|valid_date',
        'scheduled_at' => 'permit_empty|valid_date',
        'sort_order' => 'required|integer',
        'sitemap_priority' => 'permit_empty|decimal',
        'sitemap_changefreq' => 'permit_empty|in_list[always,hourly,daily,weekly,monthly,yearly,never]',
        'is_in_sitemap' => 'permit_empty|boolean_like',
    ];
}
