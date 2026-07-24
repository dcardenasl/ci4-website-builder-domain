<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use dcardenasl\Ci4ApiCore\DataCasts\DecimalCast;

class EntryEntity extends Entity
{
    protected $castHandlers = [
        'decimal' => DecimalCast::class,
    ];

    protected $casts = [
        'id' => 'integer',
        'collection_id' => 'int',
        'author_id' => 'int',
        'workflow_status' => 'string',
        'published_at' => 'string',
        'scheduled_at' => 'string',
        'is_featured' => 'bool',
        'view_count' => 'int',
        'sort_order' => 'int',
        'sitemap_priority' => 'decimal',
        'sitemap_changefreq' => 'string',
        'is_in_sitemap' => 'bool',
        'wizard_extra'  => 'json',
        'translations' => 'array',
        'categories' => 'array',
        'tags' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
