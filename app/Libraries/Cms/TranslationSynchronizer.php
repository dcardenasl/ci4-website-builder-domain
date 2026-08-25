<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Synchronizes a resource's translations in one database transaction using
 * only the required inserts, updates and deletes. Resource services provide
 * the table-specific row mapping.
 */
/**
 * Backwards-compatible CMS name for the shared translation-table adapter.
 * New domain modules should depend on TranslationTableSynchronizer directly.
 */
class TranslationSynchronizer extends \App\Libraries\Translation\TranslationTableSynchronizer
{
}
