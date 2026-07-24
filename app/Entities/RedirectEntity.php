<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class RedirectEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'old_path' => 'string',
        'new_url' => 'string',
        'redirect_type' => 'int',
        'is_active' => 'bool',
        'hit_count' => 'int',
        'note' => 'string',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
