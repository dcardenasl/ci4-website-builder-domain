<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

final class PagePresetResolver
{
    /**
     * @return array{type_key: string, label: string, version: string, block_template: array<string, mixed>, wizard_config: null}
     */
    public static function resolve(string $pageType): array
    {
        return CmsPresetCatalog::resolvePage($pageType);
    }
}
