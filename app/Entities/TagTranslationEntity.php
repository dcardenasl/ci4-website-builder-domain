<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TagTranslationEntity extends Entity
{
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = [
        'id'          => 'integer',
        'tag_id'      => 'integer',
        'language_id' => 'integer',
        'slug'        => 'string',
        'name'        => 'string',
    ];
}
