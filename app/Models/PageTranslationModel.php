<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\PageTranslationEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;

class PageTranslationModel extends BaseAuditableModel
{
    protected $table = 'cms_page_translations';
    protected $primaryKey = 'id';
    protected $returnType = PageTranslationEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'page_id',
        'language_id',
        'slug',
        'title',
        'excerpt',
        'meta_title',
        'meta_description',
        'og_image_file_id',
        'og_image_url',
        'og_type',
        'canonical_url',
        'robots',
        'schema_data',
    ];

    public function isSlugAvailable(string $slug, int $languageId, ?int $currentPageId = null): bool
    {
        $builder = $this->where('slug', $slug)->where('language_id', $languageId);
        if ($currentPageId !== null) {
            $builder = $builder->where('page_id !=', $currentPageId);
        }
        return $builder->countAllResults() === 0;
    }

    protected $validationRules = [
        'page_id'          => 'required|is_natural_no_zero',
        'language_id'      => 'required|is_natural_no_zero',
        'slug'             => 'required|min_length[1]|max_length[150]',
        'title'            => 'required|min_length[1]|max_length[255]',
        'excerpt'          => 'permit_empty|max_length[500]',
        'meta_title'       => 'permit_empty|max_length[255]',
        'meta_description' => 'permit_empty|max_length[500]',
        'og_image_file_id' => 'permit_empty|integer',
        'og_image_url'     => 'permit_empty|max_length[2048]',
        'og_type'          => 'permit_empty|max_length[50]',
        'canonical_url'    => 'permit_empty|max_length[500]',
        'robots'           => 'permit_empty|max_length[100]',
        'schema_data'      => 'permit_empty|json',
    ];
}
