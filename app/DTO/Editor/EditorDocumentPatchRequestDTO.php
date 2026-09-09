<?php

declare(strict_types=1);

namespace App\DTO\Editor;

use Config\Editor;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

final readonly class EditorDocumentPatchRequestDTO implements DataTransferObjectInterface
{
    /** @param list<array<string, mixed>> $ops */
    private function __construct(public string $baseVersion, public array $ops)
    {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, Editor $limits): self
    {
        $version = $data['base_version'] ?? null;
        $ops = $data['ops'] ?? null;
        if (! is_string($version) || preg_match('/^[a-f0-9]{64}$/D', $version) !== 1
            || ! is_array($ops) || ! array_is_list($ops) || $ops === [] || count($ops) > $limits->maxOperations
            || array_diff(array_keys($data), ['base_version', 'ops']) !== []) {
            throw new ValidationException(lang('Editor.invalidPayload'));
        }
        foreach ($ops as $op) {
            if (! is_array($op) || array_is_list($op) || ! in_array($op['op'] ?? null, ['upsert', 'delete', 'reorder'], true)) {
                throw new ValidationException(lang('Editor.invalidPayload'));
            }
        }

        return new self($version, $ops);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['base_version' => $this->baseVersion, 'ops' => $this->ops];
    }
}
