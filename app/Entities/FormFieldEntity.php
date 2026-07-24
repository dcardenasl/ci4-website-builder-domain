<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class FormFieldEntity extends Entity
{
    /** @var array<string, string> */
    protected $casts = [
        'id'            => 'integer',
        'form_id'       => 'integer',
        'field_key'     => 'string',
        'field_type'    => 'string',
        'options'       => 'json-array',
        'display_order' => 'integer',
        'is_required'   => 'boolean',
        'is_active'     => 'boolean',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
