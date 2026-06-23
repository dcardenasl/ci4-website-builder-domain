<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\SettingTranslationEntity;
use CodeIgniter\Model;

class SettingTranslationModel extends Model
{
    protected $table = 'cms_setting_translations';
    protected $primaryKey = 'id';
    protected $returnType = SettingTranslationEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'setting_id',
        'language_id',
        'setting_value',
    ];

    protected $validationRules = [
        'setting_id'    => 'required|is_natural_no_zero',
        'language_id'   => 'required|is_natural_no_zero',
        'setting_value' => 'permit_empty|string',
    ];
}
