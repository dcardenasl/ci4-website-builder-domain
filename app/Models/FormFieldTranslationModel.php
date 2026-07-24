<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FormFieldTranslationModel extends Model
{
    protected $table      = 'cms_form_field_translations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    /** @var list<string> */
    protected $allowedFields = [
        'form_field_id',
        'language_id',
        'label',
        'placeholder',
        'help_text',
        'option_labels',
        'error_required',
        'error_invalid',
    ];
}
