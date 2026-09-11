<?php

declare(strict_types=1);

namespace App\DTO\Editor;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;

/** A validated draft in the shape the public block renderer already consumes. */
final readonly class EditorPreviewDocumentDTO implements DataTransferObjectInterface
{
    /**
     * @param list<array<string, mixed>> $blocks nested tree, renderer shape
     * @param list<string> $fallbackRefs editor refs rendering default-language copy
     */
    public function __construct(
        public string $lang,
        public array $blocks,
        public string $scopeType,
        public ?string $scopeRef,
        public array $fallbackRefs = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
