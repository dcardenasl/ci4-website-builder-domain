<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class MenuItemTranslationEntity extends Entity
{
    protected $casts = [
        'id'           => 'integer',
        'menu_item_id' => 'integer',
        'language_id'  => 'integer',
        'label'        => 'string',
        'custom_url'   => 'string',
    ];
}
