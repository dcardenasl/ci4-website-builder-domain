<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\FormFieldEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;

class FormFieldModel extends BaseAuditableModel
{
    protected $table      = 'cms_form_fields';
    protected $primaryKey = 'id';
    protected $returnType = FormFieldEntity::class;
    protected $useTimestamps = true;

    /** @var list<string> */
    protected $allowedFields = [
        'form_id',
        'field_key',
        'field_type',
        'options',
        'display_order',
        'is_required',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $validationRules = [
        'form_id'    => 'required|integer',
        'field_key'  => 'required|alpha_dash|max_length[100]',
        'field_type' => 'required|in_list[text,email,phone,textarea,select,radio,checkbox,date,number,url]',
        'options'    => 'permit_empty',
    ];
}
