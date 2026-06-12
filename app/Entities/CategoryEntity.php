<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CategoryEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'collection_id' => 'int',
        'parent_id' => 'int',
        'sort_order' => 'int',
        'is_active' => 'bool',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
