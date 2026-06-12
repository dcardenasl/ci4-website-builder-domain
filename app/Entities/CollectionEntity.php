<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use dcardenasl\Ci4ApiCore\DataCasts\DecimalCast;

class CollectionEntity extends Entity
{
    protected $castHandlers = [
        'decimal' => DecimalCast::class,
    ];

    protected $casts = [
        'id' => 'integer',
        'collection_key' => 'string',
        'url_prefix' => 'string',
        'is_active' => 'bool',
        'requires_approval' => 'bool',
        'enables_categories' => 'bool',
        'enables_tags' => 'bool',
        'default_sitemap_priority' => 'decimal',
        'default_changefreq' => 'string',
        'sort_order' => 'int',
        'translations' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
