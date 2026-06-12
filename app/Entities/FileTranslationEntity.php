<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class FileTranslationEntity extends Entity
{
    protected $casts = [
        'id'          => 'integer',
        'file_id'     => 'integer',
        'language_id' => 'integer',
        'alt_text'    => 'string',
        'caption'     => 'string',
        'title'       => 'string',
        'credit'      => 'string',
        'description' => 'string',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
