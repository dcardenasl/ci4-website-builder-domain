<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\SettingEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class SettingModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_settings';
    protected $primaryKey = 'id';
    protected $returnType = SettingEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'setting_key',
        'setting_value',
        'setting_meta',
        'setting_type',
        'input_type',
        'options_json',
        'setting_group',
        'is_translatable',
        'is_public',
        'is_active',
        'is_required',
        'is_readonly',
        'sort_order',
        'description',
    ];

    /** @var array<int, string> */
    protected array $searchableFields = ['setting_key', 'setting_group', 'description'];

    /** @var array<int, string> */
    protected array $filterableFields = ['setting_group', 'is_translatable'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'sort_order', 'setting_key'];

    protected $validationRules = [
        'setting_key'     => 'required|string|max_length[100]',
        'setting_value'   => 'permit_empty|string',
        'setting_type'    => 'required|in_list[string,int,bool,json,file_id]',
        'input_type'      => 'permit_empty|in_list[text,textarea,richtext,url,email,phone,color,number,boolean,image,file,select,code,slug]',
        'options_json'    => 'permit_empty|string',
        'setting_group'   => 'permit_empty|string|max_length[50]',
        'is_translatable' => 'permit_empty|boolean_like',
        'is_required'     => 'permit_empty|boolean_like',
        'is_readonly'     => 'permit_empty|boolean_like',
        'sort_order'      => 'permit_empty|integer',
        'description'     => 'permit_empty|string|max_length[255]',
    ];
}
