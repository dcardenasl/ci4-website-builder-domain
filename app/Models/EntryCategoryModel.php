<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pivot between entries and categories (`cms_entry_categories`).
 *
 * The table has a composite primary key (`entry_id`, `category_id`) and no
 * surrogate `id`, so this model deliberately does not use `BaseAuditableModel`
 * or the generic repository: `find()`/`update($id)` have no meaning here. It
 * exists so the table name, its columns and its ordering rule have exactly one
 * owner instead of being spelled out inline wherever a service needed them.
 *
 * `sort_order` is meaningful: a category list is presented in the order the
 * editor arranged it.
 */
class EntryCategoryModel extends Model
{
    protected $table = 'cms_entry_categories';
    protected $primaryKey = 'entry_id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
    protected $useAutoIncrement = false;

    protected $allowedFields = ['entry_id', 'category_id', 'sort_order'];
}
