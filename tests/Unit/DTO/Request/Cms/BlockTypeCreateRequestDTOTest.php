<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\BlockTypeCreateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BlockTypeCreateRequestDTOTest extends CIUnitTestCase
{
    public function testBlockKeyUniqueRuleTargetsTheRealTable(): void
    {
        $reflection = new \ReflectionClass(BlockTypeCreateRequestDTO::class);
        /** @var BlockTypeCreateRequestDTO $dto */
        $dto = $reflection->newInstanceWithoutConstructor();

        $this->assertSame('required|string|max_length[255]|is_unique[cms_content_blocks.block_key]', $dto->rules()['block_key']);
    }
}
