<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class PageTranslationEntity extends Entity
{
    protected $casts = [
        'id'               => 'integer',
        'page_id'          => 'integer',
        'language_id'      => 'integer',
        'slug'             => 'string',
        'title'            => 'string',
        'excerpt'          => 'string',
        'meta_title'       => 'string',
        'meta_description' => 'string',
        'og_image_file_id' => 'integer',
        'og_image_url'     => 'string',
        'og_type'          => 'string',
        'canonical_url'    => 'string',
        'robots'           => 'string',
        'schema_data'      => 'json',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
