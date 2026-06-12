<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\EntryVersionEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;

class EntryVersionModel extends BaseAuditableModel
{
    protected $table = 'cms_entry_versions';
    protected $primaryKey = 'id';
    protected $returnType = EntryVersionEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'entry_id',
        'version_number',
        'snapshot',
        'created_by',
        'note',
    ];

    protected $validationRules = [
        'entry_id'       => 'required|is_natural_no_zero',
        'version_number' => 'required|integer',
        'snapshot'       => 'required|json',
        'created_by'     => 'permit_empty|is_natural_no_zero',
        'note'           => 'permit_empty|string|max_length[255]',
    ];
}
