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
        'setting_meta'    => 'json-array',
        'setting_type'    => 'string',
        'input_type'      => 'string',
        'options_json'    => 'json-array',
        'setting_group'   => 'string',
        'is_translatable' => 'boolean',
        'is_required'     => 'boolean',
        'is_readonly'     => 'boolean',
        'sort_order'      => 'integer',
        'description'     => 'string',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
