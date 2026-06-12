<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class SettingEntity extends Entity
{
    protected $casts = [
        'id'              => 'integer',
        'setting_key'     => 'string',
        'setting_value'   => 'string',
        'setting_type'    => 'string',
        'setting_group'   => 'string',
        'is_translatable' => 'boolean',
        'sort_order'      => 'integer',
        'description'     => 'string',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
