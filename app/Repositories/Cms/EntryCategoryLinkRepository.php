<?php

declare(strict_types=1);

namespace App\Repositories\Cms;

use App\Interfaces\Cms\EntryTaxonomyLinkRepositoryInterface;
use App\Models\EntryCategoryModel;

/**
 * Replaces an entry's category links, preserving the given order as `sort_order`.
 */
final class EntryCategoryLinkRepository implements EntryTaxonomyLinkRepositoryInterface
{
    public function __construct(private readonly EntryCategoryModel $model)
    {
    }

    public function replaceForEntry(int $entryId, array $relatedIds): void
    {
        $this->model->where('entry_id', $entryId)->delete();

        if ($relatedIds === []) {
            return;
        }

        $rows = [];

        foreach (array_values($relatedIds) as $position => $categoryId) {
            $rows[] = [
                'entry_id'    => $entryId,
                'category_id' => $categoryId,
                'sort_order'  => $position,
            ];
        }

        $this->model->insertBatch($rows);
    }
}
