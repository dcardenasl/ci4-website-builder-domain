<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\CategoryCreateRequestDTO;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

/**
 * @internal
 */
final class CategoryCreateRequestDTOValidationTest extends CIUnitTestCase
{
    public function testCategoryCreateDtoAcceptsMissingSortOrderAndDefaultsToZero(): void
    {
        $payload = [
            'collection_id' => 1,
            'parent_id' => null,
            'is_active' => true,
            'translations' => [
                [
                    'language_id' => 1,
                    'slug' => 'test-category',
                    'name' => 'Test Category',
                ],
            ],
        ];

        try {
            $dto = new CategoryCreateRequestDTO($payload, Services::validation());
            $this->assertSame(0, $dto->sort_order);
            $this->assertTrue(true);
        } catch (ValidationException $e) {
            $this->fail($e->getMessage() . ' | ' . json_encode($e->getErrors(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }
}
