<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

final class CollectionPresetResolver
{
    /**
     * @return array{type_key: string, label: string, version: string, block_template: array<string, mixed>, wizard_config: array<string, mixed>|null}
     */
    public static function resolve(string $collectionType): array
    {
        return CmsPresetCatalog::resolveCollection($collectionType);
    }
}
