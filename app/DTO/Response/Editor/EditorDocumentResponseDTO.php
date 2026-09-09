<?php

declare(strict_types=1);

namespace App\DTO\Response\Editor;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;

final readonly class EditorDocumentResponseDTO implements DataTransferObjectInterface
{
    /**
     * @param array<string, mixed> $owner
     * @param list<array<string, mixed>> $locales
     * @param list<array<string, mixed>> $blocks
     * @param list<array<string, mixed>> $catalog
     * @param array<int, array{url: string|null, variants: array<string, mixed>|null}> $media
     * @param array<string, mixed> $template
     */
    public function __construct(
        public array $owner,
        public string $version,
        public array $locales,
        public array $blocks,
        public array $catalog,
        public array $template,
        public array $media = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
