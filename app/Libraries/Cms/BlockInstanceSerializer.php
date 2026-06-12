<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use Config\Services;

class BlockInstanceSerializer
{
    /**
     * Resolve and serialize all block instances for a given owner.
     *
     * @param string $ownerType Type of owner ('page', 'entry')
     * @param int $ownerId ID of the owner
     * @param string $langCode Target language code
     * @return array<int, array<string, mixed>>
     */
    public function forContent(string $ownerType, int $ownerId, string $langCode): array
    {
        $db = \Config\Database::connect();
        $query = $db->table('cms_block_instances i')
            ->select('i.*, b.block_key, b.name as block_type_name')
            ->join('cms_content_blocks b', 'b.id = i.block_id')
            ->where('i.owner_type', $ownerType)
            ->where('i.owner_id', $ownerId)
            ->where('i.is_active', 1)
            ->orderBy('i.sort_order', 'ASC')
            ->get();

        $instances = $query ? $query->getResultArray() : [];

        $serialized = [];
        $resolver = Services::translationResolver();

        foreach ($instances as $instance) {
            $instanceId = (int) $instance['id'];
            $resolved = $resolver->resolve('block_instance', $instanceId, $langCode);

            // Parse block_data
            $blockData = [];
            if (!empty($resolved['block_data'])) {
                $blockData = is_string($resolved['block_data'])
                    ? (json_decode($resolved['block_data'], true) ?? [])
                    : (array) $resolved['block_data'];
            }

            // Decode block_config
            $blockConfig = [];
            if (!empty($instance['block_config'])) {
                $blockConfig = is_string($instance['block_config'])
                    ? (json_decode($instance['block_config'], true) ?? [])
                    : (array) $instance['block_config'];
            }

            // Prepare base payload
            $blockPayload = [
                'id'           => $instanceId,
                'block_key'    => $instance['block_key'],
                'sort_order'   => (int) $instance['sort_order'],
                'column_index' => isset($instance['column_index']) ? (int) $instance['column_index'] : null,
                'block_config' => $blockConfig,
                'block_data'   => $blockData,
                'is_fallback'  => $resolved['is_fallback'] ?? false,
            ];

            // If it is an image block, enrich it
            if ($instance['block_key'] === 'image') {
                $blockPayload = $this->enrichImageBlock($blockPayload, $langCode);
            }

            $serialized[] = $blockPayload;
        }

        return $serialized;
    }

    /**
     * Enrich a block containing a file_id (like an image block) with translated metadata.
     *
     * @param array<string, mixed> $block Raw block data
     * @param string $langCode Target language code
     * @return array<string, mixed> Enriched block data
     */
    public function enrichImageBlock(array $block, string $langCode): array
    {
        $fileId = $block['block_data']['file_id'] ?? $block['file_id'] ?? null;
        if ($fileId === null) {
            return $block;
        }

        $resolver = Services::translationResolver();
        $resolved = $resolver->resolve('file', (int) $fileId, $langCode);

        // Put alt_text, caption, etc., inside block_data for consistency or merge directly
        $block['block_data']['alt_text'] = $resolved['alt_text'] ?? null;
        $block['block_data']['caption'] = $resolved['caption'] ?? null;
        $block['block_data']['title'] = $resolved['title'] ?? null;
        $block['block_data']['credit'] = $resolved['credit'] ?? null;
        $block['block_data']['description'] = $resolved['description'] ?? null;
        $block['block_data']['file_is_fallback'] = $resolved['is_fallback'] ?? false;

        return $block;
    }
}
