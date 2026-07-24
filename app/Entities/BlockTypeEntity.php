<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class BlockTypeEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'block_key' => 'string',
        'name' => 'string',
        'description' => 'string',
        'category' => 'string',
        'icon' => 'string',
        'schema_definition' => 'json',
        'supports_pages' => 'bool',
        'supports_entries' => 'bool',
        'is_container' => 'bool',
        'is_active' => 'bool',
        'sort_order' => 'int',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
