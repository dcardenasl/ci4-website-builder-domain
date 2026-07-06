<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeCmsGenericBlockKeys extends Migration
{
    /** @var array<string, string> */
    private array $blockKeyMap = [
        'news_grid'           => 'collection_grid',
        'portfolio_grid'      => 'collection_grid',
        'events_grid'         => 'collection_grid',
        'contact_form'        => 'form_embed',
        'location_info'       => 'contact_info',
        'faq_accordion'       => 'accordion',
        'faq_item'            => 'accordion_item',
        'features_grid'       => 'cards_grid',
        'feature_card'        => 'card_item',
        'testimonials_slider' => 'cards_slider',
        'testimonial_card'    => 'slide_card',
        'logo_showcase'       => 'asset_showcase',
        'logo_item'           => 'asset_item',
        'stats_section'       => 'metrics_grid',
        'stat_item'           => 'metric_item',
    ];

    public function up(): void
    {
        if (! $this->db->tableExists('cms_content_blocks')) {
            return;
        }

        foreach ($this->blockKeyMap as $oldKey => $newKey) {
            $this->mergeBlockType($oldKey, $newKey);
        }

        $this->normalizeContainerSchemas();
        $this->normalizeAccordionTranslations();
        $this->normalizeCollectionGridConfigs();
    }

    public function down(): void
    {
        // This is a forward-only normalization. Recreating removed block keys would
        // reintroduce the inconsistent model this migration removes.
    }

    private function mergeBlockType(string $oldKey, string $newKey): void
    {
        $old = $this->findBlockType($oldKey);
        if ($old === null) {
            return;
        }

        $instanceIds = $this->findInstanceIdsForBlock((int) $old['id']);
        $new = $this->findBlockType($newKey);
        if ($new === null) {
            $schema = $this->replaceBlockKeysInJson((string) ($old['schema_definition'] ?? '{}'));
            $this->db->table('cms_content_blocks')
                ->where('id', (int) $old['id'])
                ->update([
                    'block_key'         => $newKey,
                    'schema_definition' => $schema,
                ]);
            $this->normalizeMovedBlockConfigs($instanceIds, $oldKey);
            return;
        }

        if ($this->db->tableExists('cms_block_instances')) {
            $this->db->table('cms_block_instances')
                ->where('block_id', (int) $old['id'])
                ->update(['block_id' => (int) $new['id']]);
        }
        $this->normalizeMovedBlockConfigs($instanceIds, $oldKey);

        $this->db->table('cms_content_blocks')
            ->where('id', (int) $old['id'])
            ->delete();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findBlockType(string $blockKey): ?array
    {
        $row = $this->db->table('cms_content_blocks')
            ->where('block_key', $blockKey)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<int>
     */
    private function findInstanceIdsForBlock(int $blockId): array
    {
        if (! $this->db->tableExists('cms_block_instances')) {
            return [];
        }

        $rows = $this->db->table('cms_block_instances')
            ->select('id')
            ->where('block_id', $blockId)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    private function normalizeContainerSchemas(): void
    {
        $rows = $this->db->table('cms_content_blocks')
            ->select('id, schema_definition')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $schema = $this->replaceBlockKeysInJson((string) ($row['schema_definition'] ?? '{}'));
            if ($schema === (string) ($row['schema_definition'] ?? '{}')) {
                continue;
            }

            $this->db->table('cms_content_blocks')
                ->where('id', (int) $row['id'])
                ->update(['schema_definition' => $schema]);
        }
    }

    private function normalizeAccordionTranslations(): void
    {
        if (! $this->db->tableExists('cms_block_instance_translations')) {
            return;
        }

        $rows = $this->db->table('cms_block_instance_translations')
            ->select('id, block_data')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $data = json_decode((string) ($row['block_data'] ?? '{}'), true);
            if (! is_array($data)) {
                continue;
            }

            $changed = false;
            if (array_key_exists('question', $data) && ! array_key_exists('title', $data)) {
                $data['title'] = $data['question'];
                unset($data['question']);
                $changed = true;
            }
            if (array_key_exists('answer', $data) && ! array_key_exists('content', $data)) {
                $data['content'] = $data['answer'];
                unset($data['answer']);
                $changed = true;
            }

            if (! $changed) {
                continue;
            }

            $this->db->table('cms_block_instance_translations')
                ->where('id', (int) $row['id'])
                ->update(['block_data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
        }
    }

    private function normalizeCollectionGridConfigs(): void
    {
        $collectionGrid = $this->findBlockType('collection_grid');
        if ($collectionGrid === null || ! $this->db->tableExists('cms_block_instances')) {
            return;
        }

        $instances = $this->db->table('cms_block_instances')
            ->select('id, block_config')
            ->where('block_id', (int) $collectionGrid['id'])
            ->get()
            ->getResultArray();

        foreach ($instances as $instance) {
            $config = json_decode((string) ($instance['block_config'] ?? '{}'), true);
            if (! is_array($config)) {
                $config = [];
            }

            $config['order_by']        = $config['order_by'] ?? 'published_at';
            $config['order_direction'] = $config['order_direction'] ?? 'desc';
            $config['layout_variant']  = $config['layout_variant'] ?? 'cards';

            $this->db->table('cms_block_instances')
                ->where('id', (int) $instance['id'])
                ->update(['block_config' => json_encode($config, JSON_UNESCAPED_UNICODE)]);
        }
    }

    /**
     * @param list<int> $instanceIds
     */
    private function normalizeMovedBlockConfigs(array $instanceIds, string $oldKey): void
    {
        if ($instanceIds === [] || ! $this->db->tableExists('cms_block_instances')) {
            return;
        }

        foreach ($instanceIds as $instanceId) {
            $row = $this->db->table('cms_block_instances')
                ->select('block_config')
                ->where('id', $instanceId)
                ->get()
                ->getRowArray();
            if (! is_array($row)) {
                continue;
            }

            $config = json_decode((string) ($row['block_config'] ?? '{}'), true);
            if (! is_array($config)) {
                $config = [];
            }

            if ($oldKey === 'portfolio_grid') {
                $config['order_by']        = $config['order_by'] ?? 'sort_order';
                $config['order_direction'] = $config['order_direction'] ?? 'asc';
                $config['layout_variant']  = $config['layout_variant'] ?? 'portfolio';
            } elseif ($oldKey === 'events_grid') {
                $config['order_by']        = $config['order_by'] ?? 'published_at';
                $config['order_direction'] = $config['order_direction'] ?? 'asc';
                $config['layout_variant']  = $config['layout_variant'] ?? 'cards';
            } elseif ($oldKey === 'news_grid') {
                $config['order_by']        = $config['order_by'] ?? 'published_at';
                $config['order_direction'] = $config['order_direction'] ?? 'desc';
                $config['layout_variant']  = $config['layout_variant'] ?? 'cards';
            } else {
                continue;
            }

            $this->db->table('cms_block_instances')
                ->where('id', $instanceId)
                ->update(['block_config' => json_encode($config, JSON_UNESCAPED_UNICODE)]);
        }
    }

    private function replaceBlockKeysInJson(string $json): string
    {
        return str_replace(array_keys($this->blockKeyMap), array_values($this->blockKeyMap), $json);
    }
}
