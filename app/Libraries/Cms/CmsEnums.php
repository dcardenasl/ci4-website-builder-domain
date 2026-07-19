<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

final class CmsEnums
{
    public const WORKFLOW_STATUS    = ['draft', 'in_review', 'approved', 'published', 'archived'];
    public const PAGE_STATUS        = ['draft', 'published', 'archived'];
    public const PAGE_TYPE          = ['home', 'generic', 'contact', 'privacy', 'terms', '404', '500', 'maintenance', 'collection_index'];
    public const SITEMAP_CHANGEFREQ = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
    public const NON_TRANSLATABLE_TYPES = ['media_reference', 'repeater', 'boolean', 'integer', 'select', 'number'];

    /** @param array<string> $values */
    public static function inListRule(array $values): string
    {
        return 'in_list[' . implode(',', $values) . ']';
    }
}
