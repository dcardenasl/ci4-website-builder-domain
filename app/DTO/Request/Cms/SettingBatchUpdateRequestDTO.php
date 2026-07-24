<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

/** Validated batch of partial setting updates. */
readonly class SettingBatchUpdateRequestDTO extends BaseRequestDTO
{
    /** @var list<array{id: int, payload: array<string, mixed>}> */
    public array $updates;

    public function rules(): array
    {
        return ['updates' => 'required|is_array'];
    }

    protected function map(array $data): void
    {
        $updates = [];
        foreach (($data['updates'] ?? []) as $update) {
            if (!is_array($update) || !isset($update['id']) || !is_array($update['payload'] ?? null)) {
                continue;
            }
            $id = filter_var($update['id'], FILTER_VALIDATE_INT);
            if ($id !== false && $id > 0) {
                $updates[] = ['id' => $id, 'payload' => $update['payload']];
            }
        }
        $this->updates = $updates;
    }

    public function toArray(): array
    {
        return ['updates' => $this->updates];
    }
}
