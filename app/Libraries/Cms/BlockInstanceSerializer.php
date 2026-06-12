<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use Config\Services;

class BlockInstanceSerializer
{
    /**
     * Enrich a block containing a file_id (like an image block) with translated metadata.
     *
     * @param array<string, mixed> $block Raw block data
     * @param string $langCode Target language code
     * @return array<string, mixed> Enriched block data
     */
    public function enrichImageBlock(array $block, string $langCode): array
    {
        $fileId = $block['file_id'] ?? null;
        if ($fileId === null) {
            return $block;
        }

        $resolver = Services::translationResolver();
        $resolved = $resolver->resolve('file', (int) $fileId, $langCode);

        $block['alt_text'] = $resolved['alt_text'] ?? null;
        $block['caption'] = $resolved['caption'] ?? null;
        $block['title'] = $resolved['title'] ?? null;
        $block['credit'] = $resolved['credit'] ?? null;
        $block['description'] = $resolved['description'] ?? null;
        $block['is_fallback'] = $resolved['is_fallback'] ?? false;

        return $block;
    }
}
