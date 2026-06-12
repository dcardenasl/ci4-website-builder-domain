<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class MenuEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'menu_key' => 'string',
        'location' => 'string',
        'is_active' => 'bool',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
