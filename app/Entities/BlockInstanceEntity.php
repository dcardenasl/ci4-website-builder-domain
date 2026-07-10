<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class BlockInstanceEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'block_id' => 'int',
        'owner_type' => 'string',
        'owner_id' => 'int',
        'parent_instance_id' => 'int',
        'sort_order' => 'int',
        'column_index' => 'int',
        'is_active' => 'bool',
        'block_config' => 'json[array]',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
