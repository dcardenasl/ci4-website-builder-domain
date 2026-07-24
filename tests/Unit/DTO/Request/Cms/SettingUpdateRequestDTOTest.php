<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\SettingUpdateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SettingUpdateRequestDTOTest extends CIUnitTestCase
{
    public function testSettingKeyRuleIgnoresCurrentIdDuringUpdate(): void
    {
        $dto = new SettingUpdateRequestDTO([
            'id' => 123,
            'setting_key' => 'site.title',
            'setting_value' => 'Updated title',
        ], service('validation'));

        $this->assertSame(
            'permit_empty|string|max_length[100]',
            $dto->rules()['setting_key']
        );
        $this->assertSame(123, $dto->toArray()['id']);
    }
}
