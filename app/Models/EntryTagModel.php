<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pivot between entries and tags (`cms_entry_tags`).
 *
 * Same shape as {@see EntryCategoryModel}: composite primary key
 * (`entry_id`, `tag_id`), no surrogate `id`, so the generic repository contract
 * does not apply. Tags carry no `sort_order` — they are an unordered set.
 */
class EntryTagModel extends Model
{
    protected $table = 'cms_entry_tags';
    protected $primaryKey = 'entry_id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
    protected $useAutoIncrement = false;

    protected $allowedFields = ['entry_id', 'tag_id'];
}
