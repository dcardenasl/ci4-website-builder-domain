<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class PageVersionEntity extends Entity
{
    protected $casts = [
        'id'             => 'integer',
        'page_id'        => 'integer',
        'version_number' => 'integer',
        'snapshot'       => 'json',
        'created_by'     => 'integer',
        'note'           => 'string',
    ];

    protected $dates = ['created_at'];
}
