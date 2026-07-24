<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CategoryTranslationEntity extends Entity
{
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = [
        'id'          => 'integer',
        'category_id' => 'integer',
        'language_id' => 'integer',
        'slug'        => 'string',
        'name'        => 'string',
        'description' => 'string',
        'meta_title'  => 'string',
        'meta_description' => 'string',
    ];
}
