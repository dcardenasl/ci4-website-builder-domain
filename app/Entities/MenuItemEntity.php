<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class MenuItemEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'menu_id' => 'int',
        'parent_id' => '?int',
        'link_type' => 'string',
        'page_id' => '?int',
        'entry_id' => '?int',
        'collection_id' => '?int',
        'link_target' => 'string',
        'icon' => 'string',
        'css_class' => 'string',
        'sort_order' => 'int',
        'is_active' => 'bool',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
