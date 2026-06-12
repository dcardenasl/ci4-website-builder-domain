<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\TagTranslationEntity;
use CodeIgniter\Model;

class TagTranslationModel extends Model
{
    protected $table = 'cms_tag_translations';
    protected $primaryKey = 'id';
    protected $returnType = TagTranslationEntity::class;
    protected $allowedFields = ['tag_id', 'language_id', 'slug', 'name'];
    protected $useTimestamps = false;

    protected $validationRules = [
        'tag_id'      => 'required|integer',
        'language_id' => 'required|integer',
        'slug'        => 'required|string|max_length[100]',
        'name'        => 'required|string|max_length[100]',
    ];
}
