<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FormTranslationModel extends Model
{
    protected $table      = 'cms_form_translations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    /** @var list<string> */
    protected $allowedFields = [
        'form_id',
        'language_id',
        'name',
        'description',
        'submit_label',
        'success_message',
        'error_message',
    ];
}
