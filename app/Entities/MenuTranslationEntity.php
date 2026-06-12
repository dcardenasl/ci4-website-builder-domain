<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class MenuTranslationEntity extends Entity
{
    protected $casts = [
        'id'          => 'integer',
        'menu_id'     => 'integer',
        'language_id' => 'integer',
        'name'        => 'string',
    ];
}
