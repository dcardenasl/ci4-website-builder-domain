<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\BlockInstanceTranslationEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;

class BlockInstanceTranslationModel extends BaseAuditableModel
{
    use Filterable;

    protected $table = 'cms_block_instance_translations';
    protected $primaryKey = 'id';
    protected $returnType = BlockInstanceTranslationEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'instance_id',
        'language_id',
        'block_data',
        'is_published',
    ];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'instance_id', 'language_id', 'is_published'];

    protected $validationRules = [
        'instance_id'  => 'required|is_natural_no_zero|is_not_unique[cms_block_instances.id]',
        'language_id'  => 'required|is_natural_no_zero|is_not_unique[cms_languages.id]',
        'block_data'   => 'required',
        'is_published' => 'permit_empty|boolean_like',
    ];
}
