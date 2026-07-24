<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use dcardenasl\Ci4ApiCore\DataCasts\DecimalCast;

class PageEntity extends Entity
{
    protected $castHandlers = [
        'decimal' => DecimalCast::class,
    ];

    protected $casts = [
        'id' => 'integer',
        'parent_id' => 'int',
        'collection_id' => 'int',
        'page_type' => 'string',
        'status' => 'string',
        'published_at' => 'string',
        'scheduled_at' => 'string',
        'sort_order' => 'int',
        'sitemap_priority' => 'decimal',
        'sitemap_changefreq' => 'string',
        'is_in_sitemap' => 'bool',
        'translations' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
