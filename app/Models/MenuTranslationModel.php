<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\MenuTranslationEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;

class MenuTranslationModel extends BaseAuditableModel
{
    protected $table = 'cms_menu_translations';
    protected $primaryKey = 'id';
    protected $returnType = MenuTranslationEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'menu_id',
        'language_id',
        'name',
    ];

    protected $validationRules = [
        'menu_id'     => 'required|is_natural_no_zero',
        'language_id' => 'required|is_natural_no_zero',
        'name'        => 'required|string|max_length[150]',
    ];
}
