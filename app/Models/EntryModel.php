<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\EntryEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class EntryModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_entries';
    protected $primaryKey = 'id';
    protected $returnType = EntryEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = ['collection_id', 'author_id', 'workflow_status', 'published_at', 'scheduled_at', 'is_featured', 'view_count', 'sort_order', 'sitemap_priority', 'sitemap_changefreq', 'is_in_sitemap'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at'];

    protected $validationRules = [
        'collection_id' => 'required|integer',
        'author_id' => 'permit_empty|integer',
        'workflow_status' => 'required|string|max_length[255]',
        'published_at' => 'permit_empty|valid_date',
        'scheduled_at' => 'permit_empty|valid_date',
        'is_featured' => 'required|boolean_like',
        'view_count' => 'required|integer',
        'sort_order' => 'required|integer',
        'sitemap_priority' => 'permit_empty|decimal',
        'sitemap_changefreq' => 'permit_empty|string|max_length[255]',
        'is_in_sitemap' => 'required|boolean_like',
    ];
}
