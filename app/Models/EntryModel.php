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
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['collection_id', 'author_id', 'workflow_status', 'published_at', 'scheduled_at', 'is_featured', 'view_count', 'sort_order', 'sitemap_priority', 'sitemap_changefreq', 'is_in_sitemap', 'wizard_extra'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'collection_id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'sort_order'];

    protected $validationRules = [
        'collection_id' => 'required|integer',
        'author_id' => 'permit_empty|integer',
        'workflow_status' => 'required|in_list[draft,in_review,approved,published,archived]',
        'published_at' => 'permit_empty|valid_date',
        'scheduled_at' => 'permit_empty|valid_date',
        'is_featured' => 'permit_empty|boolean_like',
        'view_count' => 'required|integer',
        'sort_order' => 'required|integer',
        'sitemap_priority' => 'permit_empty|decimal',
        'sitemap_changefreq' => 'permit_empty|in_list[always,hourly,daily,weekly,monthly,yearly,never]',
        'is_in_sitemap' => 'permit_empty|boolean_like',
        'wizard_extra'  => 'permit_empty',
    ];
}
