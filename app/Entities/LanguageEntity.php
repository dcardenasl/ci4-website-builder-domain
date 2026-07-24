<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class LanguageEntity extends Entity
{
    protected $casts = [
        'id'                   => 'integer',
        'code'                 => 'string',
        'name'                 => 'string',
        'native_name'          => 'string',
        'is_default'           => 'boolean',
        'is_active'            => 'boolean',
        'fallback_language_id' => 'integer',
        'sort_order'           => 'integer',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
