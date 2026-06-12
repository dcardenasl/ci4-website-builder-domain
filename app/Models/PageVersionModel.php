<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\PageVersionEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;

class PageVersionModel extends BaseAuditableModel
{
    protected $table = 'cms_page_versions';
    protected $primaryKey = 'id';
    protected $returnType = PageVersionEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'page_id',
        'version_number',
        'snapshot',
        'created_by',
        'note',
    ];

    protected $validationRules = [
        'page_id'        => 'required|is_natural_no_zero',
        'version_number' => 'required|integer',
        'snapshot'       => 'required|json',
        'created_by'     => 'permit_empty|is_natural_no_zero',
        'note'           => 'permit_empty|string|max_length[255]',
    ];
}
