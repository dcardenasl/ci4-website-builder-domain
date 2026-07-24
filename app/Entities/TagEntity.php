<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TagEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'is_active' => 'bool',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
