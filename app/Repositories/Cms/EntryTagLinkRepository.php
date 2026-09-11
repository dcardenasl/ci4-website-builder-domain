<?php

declare(strict_types=1);

namespace App\Repositories\Cms;

use App\Interfaces\Cms\EntryTaxonomyLinkRepositoryInterface;
use App\Models\EntryTagModel;

/**
 * Replaces an entry's tag links. Tags are an unordered set — the pivot has no
 * `sort_order` column, so the input order is not persisted.
 */
final class EntryTagLinkRepository implements EntryTaxonomyLinkRepositoryInterface
{
    public function __construct(private readonly EntryTagModel $model)
    {
    }

    public function replaceForEntry(int $entryId, array $relatedIds): void
    {
        $this->model->where('entry_id', $entryId)->delete();

        if ($relatedIds === []) {
            return;
        }

        $rows = [];

        foreach ($relatedIds as $tagId) {
            $rows[] = ['entry_id' => $entryId, 'tag_id' => $tagId];
        }

        $this->model->insertBatch($rows);
    }
}
