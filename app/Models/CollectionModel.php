<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\CollectionEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class CollectionModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_collections';
    protected $primaryKey = 'id';
    protected $returnType = CollectionEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = ['collection_key', 'is_active', 'requires_approval', 'enables_categories', 'enables_tags', 'default_sitemap_priority', 'default_changefreq', 'sort_order', 'block_template'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'is_active'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'is_active'];

    protected $validationRules = [
        'collection_key' => 'required|string|max_length[50]|is_unique[cms_collections.collection_key]',
        'is_active' => 'permit_empty|boolean_like',
        'requires_approval' => 'permit_empty|boolean_like',
        'enables_categories' => 'permit_empty|boolean_like',
        'enables_tags' => 'permit_empty|boolean_like',
        'default_sitemap_priority' => 'permit_empty|decimal',
        'default_changefreq' => 'permit_empty|in_list[always,hourly,daily,weekly,monthly,yearly,never]',
        'sort_order' => 'required|integer',
        'block_template' => 'permit_empty',
    ];
}
