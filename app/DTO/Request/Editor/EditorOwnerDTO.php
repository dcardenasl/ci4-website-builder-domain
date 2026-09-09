<?php

declare(strict_types=1);

namespace App\DTO\Request\Editor;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;

final readonly class EditorOwnerDTO implements DataTransferObjectInterface
{
    public function __construct(public string $type, public int $id)
    {
        if (! in_array($type, ['page', 'entry'], true) || $id < 1) {
            throw new \InvalidArgumentException('Invalid editor document owner.');
        }
    }

    public function permission(): string
    {
        return $this->type === 'page' ? 'cms.pages.write' : 'cms.entries.write';
    }

    /** @return array{type: string, id: int} */
    public function toArray(): array
    {
        return ['type' => $this->type, 'id' => $this->id];
    }
}
