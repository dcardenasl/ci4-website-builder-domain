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
        'collection_type' => 'string',
        'is_active' => 'bool',
        'requires_approval' => 'bool',
        'enables_categories' => 'bool',
        'enables_tags' => 'bool',
        'default_sitemap_priority' => 'decimal',
        'default_changefreq' => 'string',
        'sort_order' => 'int',
        'block_template' => 'json',
        'wizard_config'  => 'json',
        'translations' => 'array',
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Returns the blocks array from the template, or null if no template is set.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function getBlocksArray(): ?array
    {
        $template = $this->block_template;
        if (is_object($template)) {
            $encoded = json_encode($template);
            if (is_string($encoded)) {
                $template = json_decode($encoded, true);
            } else {
                return null;
            }
        }
        if (!is_array($template) || !isset($template['blocks']) || !is_array($template['blocks'])) {
            return null;
        }
        return $template['blocks'];
    }

    /**
     * Returns true when the given block_key is marked locked in the template.
     */
    public function isBlockLocked(string $blockKey): bool
    {
        $blocks = $this->getBlocksArray();
        if ($blocks === null) {
            return false;
        }
        foreach ($blocks as $block) {
            if (isset($block['block_key'], $block['locked'])
                && $block['block_key'] === $blockKey
                && $block['locked'] === true
            ) {
                return true;
            }
        }
        return false;
    }
}
