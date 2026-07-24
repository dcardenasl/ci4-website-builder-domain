<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\EntrySetCategoriesRequestDTO;
use App\DTO\Request\Cms\EntrySetTagsRequestDTO;
use App\DTO\Request\Cms\EntrySyncTaxonomyRequestDTO;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

/**
 * @internal
 */
final class EntryTaxonomyRequestDTOTest extends CIUnitTestCase
{
    public function testEmptyCategoryListIsValidAndClearsAssignments(): void
    {
        try {
            $dto = new EntrySetCategoriesRequestDTO(['category_ids' => []], Services::validation());
            $this->assertSame([], $dto->category_ids);
        } catch (ValidationException $e) {
            $this->fail(json_encode($e->getErrors(), JSON_THROW_ON_ERROR));
        }
    }

    public function testEmptyTagListIsValidAndClearsAssignments(): void
    {
        try {
            $dto = new EntrySetTagsRequestDTO(['tag_ids' => []], Services::validation());
            $this->assertSame([], $dto->tag_ids);
        } catch (ValidationException $e) {
            $this->fail(json_encode($e->getErrors(), JSON_THROW_ON_ERROR));
        }
    }

    public function testInvalidTaxonomyIdsAreRejected(): void
    {
        $this->expectException(ValidationException::class);

        new EntrySetTagsRequestDTO(['tag_ids' => ['']], Services::validation());
    }

    public function testAtomicTaxonomyRequestAcceptsEmptyLists(): void
    {
        $dto = new EntrySyncTaxonomyRequestDTO([
            'category_ids' => [],
            'tag_ids' => [],
        ], Services::validation());

        $this->assertSame([], $dto->category_ids);
        $this->assertSame([], $dto->tag_ids);
    }
}
