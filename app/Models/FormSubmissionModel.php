<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\FormSubmissionEntity;
use CodeIgniter\Model;

class FormSubmissionModel extends Model
{
    protected $table      = 'cms_form_submissions';
    protected $primaryKey = 'id';
    protected $returnType = FormSubmissionEntity::class;

    protected $useTimestamps  = true;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'form_id',
        'form_key',
        'page_id',
        'language_id',
        'data_json',
        'status',
        'ip_address',
        'user_agent',
        'is_anonymized',
        'anonymized_at',
    ];

    /** @var array<string, string> */
    protected $validationRules = [
        'form_key'  => 'required|string|max_length[50]',
        'data_json' => 'required|string',
        'status'    => 'permit_empty|in_list[new,read,replied,spam,archived]',
    ];
}
