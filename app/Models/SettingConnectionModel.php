<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\SettingConnectionEntity;
use CodeIgniter\Model;

class SettingConnectionModel extends Model
{
    protected $table      = 'cms_setting_connections';
    protected $primaryKey = 'id';
    protected $returnType = SettingConnectionEntity::class;

    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'setting_id',
        'entity_type',
        'entity_key',
        'usage_label',
    ];

    protected $validationRules = [
        'setting_id'  => 'required|is_natural_no_zero',
        'entity_type' => 'required|in_list[block_type,form,collection,module]',
        'entity_key'  => 'required|string|max_length[100]',
        'usage_label' => 'permit_empty|string|max_length[255]',
    ];
}
