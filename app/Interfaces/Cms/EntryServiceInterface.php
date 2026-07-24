<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use App\DTO\Request\Cms\EntrySetCategoriesRequestDTO;
use App\DTO\Request\Cms\EntrySetTagsRequestDTO;
use App\DTO\Request\Cms\EntrySyncTaxonomyRequestDTO;
use App\DTO\Request\Cms\PublicEntryIndexRequestDTO;
use App\DTO\Request\Cms\PublicEntryShowRequestDTO;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface EntryServiceInterface extends CrudServiceContract
{
    public function syncCategories(
        int $entryId,
        EntrySetCategoriesRequestDTO $dto,
        ?SecurityContext $context = null
    ): DataTransferObjectInterface;

    public function syncTags(
        int $entryId,
        EntrySetTagsRequestDTO $dto,
        ?SecurityContext $context = null
    ): DataTransferObjectInterface;

    public function syncTaxonomy(
        int $entryId,
        EntrySyncTaxonomyRequestDTO $dto,
        ?SecurityContext $context = null
    ): DataTransferObjectInterface;

    public function listPublic(PublicEntryIndexRequestDTO $dto): DataTransferObjectInterface;

    public function showPublic(PublicEntryShowRequestDTO $dto): DataTransferObjectInterface;

    public function isSlugAvailable(string $slug, int $languageId, ?int $currentId = null): bool;
}
