<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class SettingConnectionEntity extends Entity
{
    protected $casts = [
        'id'          => 'integer',
        'setting_id'  => 'integer',
        'entity_type' => 'string',
        'entity_key'  => 'string',
        'usage_label' => 'string',
    ];

    protected $dates = ['created_at'];
}
