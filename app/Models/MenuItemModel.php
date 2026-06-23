<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\MenuItemEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class MenuItemModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_menu_items';
    protected $primaryKey = 'id';
    protected $returnType = MenuItemEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = ['menu_id', 'parent_id', 'link_type', 'page_id', 'entry_id', 'collection_id', 'link_target', 'icon', 'css_class', 'sort_order', 'is_active'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'menu_id', 'parent_id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at'];

    protected $validationRules = [
        'menu_id' => 'required|integer',
        'parent_id' => 'permit_empty|integer',
        'link_type' => 'required|in_list[page,entry,collection_listing,custom_url,no_link]',
        'page_id' => 'permit_empty|integer',
        'entry_id' => 'permit_empty|integer',
        'collection_id' => 'permit_empty|integer',
        'link_target' => 'required|in_list[_self,_blank]',
        'icon' => 'permit_empty|string|max_length[50]',
        'css_class' => 'permit_empty|string|max_length[100]',
        'sort_order' => 'required|integer',
        'is_active' => 'permit_empty|boolean_like',
    ];
}
