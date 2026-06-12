<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\FileTranslationEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class FileTranslationModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'cms_file_translations';
    protected $primaryKey = 'id';
    protected $returnType = FileTranslationEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'file_id',
        'language_id',
        'alt_text',
        'caption',
        'title',
        'credit',
        'description',
    ];

    /** @var array<int, string> */
    protected array $searchableFields = ['alt_text', 'caption', 'title', 'credit', 'description'];

    /** @var array<int, string> */
    protected array $filterableFields = ['file_id', 'language_id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'file_id', 'language_id'];

    protected $validationRules = [
        'file_id'     => 'required|is_natural_no_zero',
        'language_id' => 'required|is_natural_no_zero',
        'alt_text'    => 'permit_empty|string|max_length[255]',
        'caption'     => 'permit_empty|string|max_length[500]',
        'title'       => 'permit_empty|string|max_length[255]',
        'credit'      => 'permit_empty|string|max_length[255]',
        'description' => 'permit_empty|string',
    ];
}
