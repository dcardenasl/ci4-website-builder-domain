<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\CategoryTranslationEntity;
use CodeIgniter\Model;

class CategoryTranslationModel extends Model
{
    protected $table = 'cms_category_translations';
    protected $primaryKey = 'id';
    protected $returnType = CategoryTranslationEntity::class;
    protected $allowedFields = ['category_id', 'language_id', 'slug', 'name', 'description', 'meta_title', 'meta_description'];
    protected $useTimestamps = false;

    public function isSlugAvailable(string $slug, int $languageId, ?int $currentCategoryId = null): bool
    {
        $builder = $this->where('slug', $slug)->where('language_id', $languageId);
        if ($currentCategoryId !== null) {
            $builder = $builder->where('category_id !=', $currentCategoryId);
        }
        return $builder->countAllResults() === 0;
    }

    protected $validationRules = [
        'category_id' => 'required|integer',
        'language_id' => 'required|integer',
        'slug'        => 'required|string|max_length[150]',
        'name'        => 'required|string|max_length[150]',
        'description' => 'permit_empty|string',
    ];
}
