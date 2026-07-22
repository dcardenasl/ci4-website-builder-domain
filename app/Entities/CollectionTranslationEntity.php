<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CollectionTranslationEntity extends Entity
{
    protected $casts = [
        'id'                       => 'integer',
        'collection_id'            => 'integer',
        'language_id'              => 'integer',
        'slug'                     => 'string',
        'name'                     => 'string',
        'description'              => 'string',
        'listing_title'            => 'string',
        'listing_intro'            => 'string',
        'default_meta_title'       => 'string',
        'default_meta_description' => 'string',
        'entry_cta_label'          => 'string',
    ];
}
