<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class FormEntity extends Entity
{
    /** @var array<string, string> */
    protected $casts = [
        'id'                   => 'integer',
        'form_key'             => 'string',
        'is_active'            => 'boolean',
        'has_captcha'          => 'boolean',
        'notify_email'         => '?string',
        'autoreply_enabled'    => 'boolean',
        'autoreply_email_field' => '?string',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
