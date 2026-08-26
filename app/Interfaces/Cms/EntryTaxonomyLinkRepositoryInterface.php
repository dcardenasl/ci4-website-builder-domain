<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

/**
 * Write contract for the entry↔taxonomy pivot tables.
 *
 * Both pivots are set-valued: an entry has exactly the categories (or tags) the
 * editor last saved, so the only write operation that makes sense is "replace
 * the whole set". Exposing it as a contract keeps `EntryService` off
 * `Database::connect()` and off the pivot table names, which is what
 * `ServiceModelDependencyConventionsTest` guards.
 */
interface EntryTaxonomyLinkRepositoryInterface
{
    /**
     * Replace every link for `$entryId` with exactly `$relatedIds`.
     *
     * Passing an empty list clears the entry's links. Implementations that
     * carry a `sort_order` persist the array order as the editor's ordering.
     *
     * @param list<int> $relatedIds
     */
    public function replaceForEntry(int $entryId, array $relatedIds): void;
}
