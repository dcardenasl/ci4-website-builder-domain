<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class SettingTranslationEntity extends Entity
{
    protected $casts = [
        'id'            => 'integer',
        'setting_id'    => 'integer',
        'language_id'   => 'integer',
        'setting_value' => 'string',
        'label'         => 'string',
        'placeholder'   => 'string',
        'help_text'     => 'string',
    ];
}
