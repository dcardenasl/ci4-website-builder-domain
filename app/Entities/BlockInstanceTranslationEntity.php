<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class BlockInstanceTranslationEntity extends Entity
{
    protected $casts = [
        'id'           => 'integer',
        'instance_id'  => 'integer',
        'language_id'  => 'integer',
        'block_data'   => 'json',
        'is_published' => 'bool',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
