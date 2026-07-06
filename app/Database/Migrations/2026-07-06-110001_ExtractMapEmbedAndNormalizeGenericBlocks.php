<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExtractMapEmbedAndNormalizeGenericBlocks extends Migration
{
    public function up(): void
    {
        $this->syncBlockTypeSchemas();
        $this->extractMapEmbedBlocks();
        $this->normalizeSlideCards();
        $this->normalizeMetricItems();
    }

    public function down(): void
    {
        // Content normalization is intentionally not reversed.
    }

    private function syncBlockTypeSchemas(): void
    {
        $schemas = [
            'contact_info' => [
                'fields' => [
                    'section_title'       => ['type' => 'string', 'label' => 'Título de sección', 'required' => false],
                    'section_description' => ['type' => 'text', 'label' => 'Descripción de sección', 'required' => false],
                    'address_label'       => ['type' => 'string', 'label' => 'Etiqueta Dirección', 'required' => false],
                    'address'             => ['type' => 'text', 'label' => 'Dirección', 'required' => false],
                    'phone_label'         => ['type' => 'string', 'label' => 'Etiqueta Teléfono', 'required' => false],
                    'phone'               => ['type' => 'string', 'label' => 'Teléfono', 'required' => false],
                    'email_label'         => ['type' => 'string', 'label' => 'Etiqueta Email', 'required' => false],
                    'email'               => ['type' => 'string', 'label' => 'Email', 'required' => false],
                    'hours_label'         => ['type' => 'string', 'label' => 'Etiqueta Horarios', 'required' => false],
                    'hours'               => ['type' => 'text', 'label' => 'Horarios', 'required' => false],
                ],
                'config_fields' => [
                    'layout'    => ['type' => 'select', 'label' => 'Layout', 'options' => ['stacked', 'two_columns'], 'default' => 'stacked', 'required' => false],
                    'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                ],
            ],
            'map_embed' => [
                'fields' => [
                    'title'   => ['type' => 'string', 'label' => 'Título', 'required' => false],
                    'caption' => ['type' => 'string', 'label' => 'Texto de apoyo', 'required' => false],
                ],
                'config_fields' => [
                    'embed_url'    => ['type' => 'url', 'label' => 'URL Embed', 'required' => true, 'default' => ''],
                    'aspect_ratio' => ['type' => 'select', 'label' => 'Proporción', 'options' => ['16/9', '4/3', '1/1'], 'default' => '16/9', 'required' => false],
                    'height'       => ['type' => 'number', 'label' => 'Alto fallback (px)', 'required' => false, 'default' => 360],
                    'css_class'    => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                ],
            ],
            'cards_slider' => [
                'fields' => [
                    'section_title'    => ['type' => 'string', 'label' => 'Título de sección', 'required' => false],
                    'section_subtitle' => ['type' => 'text', 'label' => 'Subtítulo de sección', 'required' => false],
                ],
                'config_fields' => [
                    'autoplay'      => ['type' => 'boolean', 'label' => 'Autoplay', 'default' => true, 'required' => false],
                    'interval'      => ['type' => 'number', 'label' => 'Intervalo (ms)', 'default' => 6000, 'required' => false],
                    'visible_count' => ['type' => 'select', 'label' => 'Tarjetas visibles', 'options' => ['1', '2', '3'], 'default' => '1', 'required' => false],
                    'card_variant'  => ['type' => 'select', 'label' => 'Variante de tarjeta', 'options' => ['editorial', 'testimonial', 'media'], 'default' => 'editorial', 'required' => false],
                    'css_class'     => ['type' => 'string', 'label' => 'Clase CSS', 'default' => '', 'required' => false],
                ],
                'allowed_children' => ['slide_card'],
            ],
            'slide_card' => [
                'fields' => [
                    'eyebrow'          => ['type' => 'string', 'label' => 'Etiqueta superior', 'required' => false],
                    'title'            => ['type' => 'string', 'label' => 'Título', 'required' => false],
                    'body'             => ['type' => 'text', 'label' => 'Texto', 'required' => false],
                    'meta_title'       => ['type' => 'string', 'label' => 'Título metadata', 'required' => false],
                    'meta_description' => ['type' => 'string', 'label' => 'Descripción metadata', 'required' => false],
                    'image'            => ['type' => 'file', 'label' => 'Imagen', 'required' => false, 'accept' => 'image/*'],
                    'rating'           => ['type' => 'select', 'label' => 'Rating', 'options' => ['0', '1', '2', '3', '4', '5'], 'default' => '0', 'required' => false],
                    'link_url'         => ['type' => 'url', 'label' => 'URL CTA', 'required' => false],
                    'link_label'       => ['type' => 'string', 'label' => 'Texto CTA', 'required' => false],
                ],
            ],
            'metric_item' => [
                'fields' => [
                    'prefix'       => ['type' => 'string', 'label' => 'Prefijo', 'required' => false],
                    'number'       => ['type' => 'string', 'label' => 'Número', 'required' => true],
                    'suffix'       => ['type' => 'string', 'label' => 'Sufijo', 'required' => false],
                    'label'        => ['type' => 'string', 'label' => 'Etiqueta', 'required' => true],
                    'description'  => ['type' => 'text', 'label' => 'Descripción', 'required' => false],
                    'source_label' => ['type' => 'string', 'label' => 'Fuente', 'required' => false],
                    'source_url'   => ['type' => 'url', 'label' => 'URL fuente', 'required' => false],
                    'icon'         => ['type' => 'string', 'label' => 'Icono', 'required' => false],
                ],
            ],
        ];

        foreach ($schemas as $blockKey => $schema) {
            $this->upsertBlockType($blockKey, $schema);
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function upsertBlockType(string $blockKey, array $schema): void
    {
        $existing = $this->db->table('cms_content_blocks')
            ->where('block_key', $blockKey)
            ->get()
            ->getRowArray();

        $common = $this->blockTypeMetadata($blockKey);
        $payload = array_merge($common, [
            'schema_definition' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        if ($existing === null) {
            $this->db->table('cms_content_blocks')->insert(array_merge($payload, [
                'block_key'  => $blockKey,
                'created_at' => date('Y-m-d H:i:s'),
            ]));
            return;
        }

        $this->db->table('cms_content_blocks')
            ->where('block_key', $blockKey)
            ->update($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function blockTypeMetadata(string $blockKey): array
    {
        return match ($blockKey) {
            'map_embed' => [
                'name' => 'Mapa Embebido', 'description' => 'Iframe de mapa configurable e independiente de los datos de contacto.',
                'category' => 'contact', 'icon' => 'map', 'supports_pages' => 1, 'supports_entries' => 1,
                'is_container' => 0, 'is_active' => 1, 'sort_order' => 65,
            ],
            'contact_info' => [
                'name' => 'Información de Contacto', 'description' => 'Datos estructurados de contacto y horarios.',
                'category' => 'contact', 'icon' => 'map-pin', 'supports_pages' => 1, 'supports_entries' => 1,
                'is_container' => 0, 'is_active' => 1, 'sort_order' => 60,
            ],
            'cards_slider' => [
                'name' => 'Slider de Tarjetas', 'description' => 'Carrusel genérico de tarjetas editoriales, testimoniales o multimedia.',
                'category' => 'content', 'icon' => 'message-circle', 'supports_pages' => 1, 'supports_entries' => 1,
                'is_container' => 1, 'is_active' => 1, 'sort_order' => 90,
            ],
            'slide_card' => [
                'name' => 'Tarjeta de Slider', 'description' => 'Tarjeta genérica para sliders: texto, metadata, imagen, rating y CTA opcionales.',
                'category' => 'content', 'icon' => 'user', 'supports_pages' => 1, 'supports_entries' => 1,
                'is_container' => 0, 'is_active' => 1, 'sort_order' => 91,
            ],
            'metric_item' => [
                'name' => 'Métrica', 'description' => 'Métrica/KPI configurable con prefijo, sufijo, descripción, fuente e icono opcional.',
                'category' => 'content', 'icon' => 'hash', 'supports_pages' => 1, 'supports_entries' => 1,
                'is_container' => 0, 'is_active' => 1, 'sort_order' => 101,
            ],
            default => [
                'name' => ucfirst(str_replace('_', ' ', $blockKey)), 'description' => '',
                'category' => 'content', 'icon' => null, 'supports_pages' => 1, 'supports_entries' => 1,
                'is_container' => 0, 'is_active' => 1, 'sort_order' => 100,
            ],
        };
    }

    private function extractMapEmbedBlocks(): void
    {
        $contactInfoId = $this->blockId('contact_info');
        $mapEmbedId    = $this->blockId('map_embed');

        if ($contactInfoId === null || $mapEmbedId === null) {
            return;
        }

        $contactBlocks = $this->db->table('cms_block_instances')
            ->where('block_id', $contactInfoId)
            ->get()
            ->getResultArray();

        foreach ($contactBlocks as $contactBlock) {
            $config = $this->decodeJson($contactBlock['block_config'] ?? null);
            $embedUrl = trim((string) ($config['map_embed_url'] ?? ''));
            unset($config['map_embed_url']);
            $config['layout'] ??= 'stacked';

            $this->db->table('cms_block_instances')
                ->where('id', (int) $contactBlock['id'])
                ->update([
                    'block_config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

            if ($embedUrl === '' || $this->hasSiblingMap((array) $contactBlock, $mapEmbedId)) {
                continue;
            }

            $sortOrder = (int) $contactBlock['sort_order'] + 1;
            $this->shiftSiblingSortOrders((array) $contactBlock, $sortOrder);

            $this->db->table('cms_block_instances')->insert([
                'block_id'            => $mapEmbedId,
                'owner_type'          => $contactBlock['owner_type'],
                'owner_id'            => (int) $contactBlock['owner_id'],
                'parent_instance_id'  => $contactBlock['parent_instance_id'] !== null ? (int) $contactBlock['parent_instance_id'] : null,
                'sort_order'          => $sortOrder,
                'column_index'        => $contactBlock['column_index'] !== null ? (int) $contactBlock['column_index'] : null,
                'is_active'           => (int) $contactBlock['is_active'],
                'block_config'        => json_encode([
                    'embed_url'    => $embedUrl,
                    'aspect_ratio' => '16/9',
                    'height'       => 360,
                    'css_class'    => '',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at'          => date('Y-m-d H:i:s'),
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);

            $mapInstanceId = (int) $this->db->insertID();
            $this->seedEmptyTranslations($mapInstanceId);
        }
    }

    /**
     * @param array<string, mixed> $block
     */
    private function hasSiblingMap(array $block, int $mapEmbedId): bool
    {
        $builder = $this->db->table('cms_block_instances')
            ->where('block_id', $mapEmbedId)
            ->where('owner_type', (string) $block['owner_type'])
            ->where('owner_id', (int) $block['owner_id']);

        if ($block['parent_instance_id'] === null) {
            $builder->where('parent_instance_id', null);
        } else {
            $builder->where('parent_instance_id', (int) $block['parent_instance_id']);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function shiftSiblingSortOrders(array $block, int $fromSortOrder): void
    {
        $builder = $this->db->table('cms_block_instances')
            ->set('sort_order', 'sort_order + 1', false)
            ->where('owner_type', (string) $block['owner_type'])
            ->where('owner_id', (int) $block['owner_id'])
            ->where('sort_order >=', $fromSortOrder);

        if ($block['parent_instance_id'] === null) {
            $builder->where('parent_instance_id', null);
        } else {
            $builder->where('parent_instance_id', (int) $block['parent_instance_id']);
        }

        $builder->update();
    }

    private function seedEmptyTranslations(int $instanceId): void
    {
        $languages = $this->db->table('cms_languages')->select('id')->get()->getResultArray();

        foreach ($languages as $language) {
            $this->db->table('cms_block_instance_translations')->insert([
                'instance_id'   => $instanceId,
                'language_id'   => (int) $language['id'],
                'block_data'    => '{}',
                'is_published'  => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function normalizeSlideCards(): void
    {
        $slideCardId = $this->blockId('slide_card');
        if ($slideCardId === null) {
            return;
        }

        $translations = $this->translationsForBlock($slideCardId);
        foreach ($translations as $translation) {
            $data = $this->decodeJson($translation['block_data'] ?? null);
            $changed = false;

            $changed = $this->moveField($data, 'quote', 'body') || $changed;
            $changed = $this->moveField($data, 'author', 'meta_title') || $changed;
            $changed = $this->moveField($data, 'role', 'meta_description') || $changed;
            $changed = $this->moveField($data, 'avatar_url', 'image_url') || $changed;
            $changed = $this->moveField($data, 'avatar', 'image_url') || $changed;

            if ($changed) {
                $this->updateTranslationData((int) $translation['id'], $data);
            }
        }
    }

    private function normalizeMetricItems(): void
    {
        $metricItemId = $this->blockId('metric_item');
        if ($metricItemId === null) {
            return;
        }

        $translations = $this->translationsForBlock($metricItemId);
        foreach ($translations as $translation) {
            $data = $this->decodeJson($translation['block_data'] ?? null);
            $number = trim((string) ($data['number'] ?? ''));

            if ($number === '' || ($data['prefix'] ?? '') !== '' || ($data['suffix'] ?? '') !== '') {
                continue;
            }

            if (! preg_match('/^([^0-9+-]*)([+-]?[0-9][0-9.,]*)(.*)$/', $number, $matches)) {
                continue;
            }

            $prefix = trim((string) $matches[1]);
            $core   = trim((string) $matches[2]);
            $suffix = trim((string) $matches[3]);

            if ($prefix === '' && $suffix === '') {
                continue;
            }

            $data['prefix'] = $prefix;
            $data['number'] = $core;
            $data['suffix'] = $suffix;
            $this->updateTranslationData((int) $translation['id'], $data);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function translationsForBlock(int $blockId): array
    {
        return $this->db->table('cms_block_instance_translations t')
            ->select('t.id, t.block_data')
            ->join('cms_block_instances i', 'i.id = t.instance_id')
            ->where('i.block_id', $blockId)
            ->get()
            ->getResultArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function moveField(array &$data, string $from, string $to): bool
    {
        if (! array_key_exists($from, $data)) {
            return false;
        }

        if (($data[$to] ?? '') === '' && ($data[$from] ?? '') !== '') {
            $data[$to] = $data[$from];
        }

        unset($data[$from]);
        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateTranslationData(int $translationId, array $data): void
    {
        $this->db->table('cms_block_instance_translations')
            ->where('id', $translationId)
            ->update([
                'block_data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    private function blockId(string $blockKey): ?int
    {
        $row = $this->db->table('cms_content_blocks')
            ->select('id')
            ->where('block_key', $blockKey)
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
