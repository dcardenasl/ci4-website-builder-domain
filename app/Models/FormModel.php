<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\FormEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;

class FormModel extends BaseAuditableModel
{
    protected $table      = 'cms_forms';
    protected $primaryKey = 'id';
    protected $returnType = FormEntity::class;
    protected $useTimestamps = true;

    /** @var list<string> */
    protected $allowedFields = [
        'form_key',
        'is_active',
        'has_captcha',
        'notify_email',
        'autoreply_enabled',
        'autoreply_email_field',
    ];

    /** @var array<string, string> */
    protected $validationRules = [
        'form_key' => 'required|alpha_dash|max_length[50]',
    ];
}
