<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\CollectionTranslationEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;

class CollectionTranslationModel extends BaseAuditableModel
{
    protected $table = 'cms_collection_translations';
    protected $primaryKey = 'id';
    protected $returnType = CollectionTranslationEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'collection_id',
        'language_id',
        'slug',
        'name',
        'description',
        'listing_title',
        'listing_intro',
        'default_meta_title',
        'default_meta_description',
        'entry_cta_label',
    ];

    protected $validationRules = [
        'collection_id'            => 'required|is_natural_no_zero',
        'language_id'              => 'required|is_natural_no_zero',
        'slug'                     => 'required|string|max_length[150]',
        'name'                     => 'required|string|max_length[150]',
        'description'              => 'permit_empty|string',
        'listing_title'            => 'permit_empty|string|max_length[255]',
        'listing_intro'            => 'permit_empty|string',
        'default_meta_title'       => 'permit_empty|string|max_length[255]',
        'default_meta_description' => 'permit_empty|string|max_length[500]',
        'entry_cta_label'          => 'permit_empty|string|max_length[100]',
    ];

    public function isSlugAvailable(string $slug, int $languageId, ?int $currentCollectionId = null): bool
    {
        $builder = $this->where('slug', $slug)->where('language_id', $languageId);
        if ($currentCollectionId !== null) {
            $builder = $builder->where('collection_id !=', $currentCollectionId);
        }

        return $builder->countAllResults() === 0;
    }
}
