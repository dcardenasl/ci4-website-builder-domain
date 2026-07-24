<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\MenuItemTranslationEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;

class MenuItemTranslationModel extends BaseAuditableModel
{
    protected $table = 'cms_menu_item_translations';
    protected $primaryKey = 'id';
    protected $returnType = MenuItemTranslationEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'menu_item_id',
        'language_id',
        'label',
        'custom_url',
    ];

    protected $validationRules = [
        'menu_item_id' => 'required|is_natural_no_zero',
        'language_id'  => 'required|is_natural_no_zero',
        'label'        => 'required|string|max_length[150]',
        'custom_url'   => 'permit_empty|string|max_length[500]',
    ];
}
