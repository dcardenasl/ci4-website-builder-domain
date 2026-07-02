<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

final class PagePresetInitializer
{
    public function initialize(int $pageId, string $pageType): void
    {
        $preset = PagePresetResolver::resolve($pageType);
        $blocks = $preset['block_template']['blocks'] ?? [];
        if (! is_array($blocks) || $blocks === []) {
            return;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            /** @var \App\Models\BlockTypeModel $blockTypeModel */
            $blockTypeModel = model(\App\Models\BlockTypeModel::class);
            /** @var \App\Models\BlockInstanceModel $blockInstanceModel */
            $blockInstanceModel = model(\App\Models\BlockInstanceModel::class);
            /** @var \App\Models\BlockInstanceTranslationModel $translationModel */
            $translationModel = model(\App\Models\BlockInstanceTranslationModel::class);
            /** @var \App\Models\LanguageModel $languageModel */
            $languageModel = model(\App\Models\LanguageModel::class);

            $activeLanguages = $languageModel->where('is_active', 1)->findAll();

            foreach ($blocks as $blockDef) {
                if (! is_array($blockDef)) {
                    continue;
                }

                $blockKey = (string) ($blockDef['block_key'] ?? '');
                if ($blockKey === '') {
                    continue;
                }

                $blockType = $blockTypeModel->where('block_key', $blockKey)->first();
                if (! $blockType instanceof \App\Entities\BlockTypeEntity) {
                    throw new \RuntimeException("Block type '{$blockKey}' not found during page preset initialization");
                }

                $sortOrder = (int) ($blockDef['sort_order'] ?? 1);
                $existing = $blockInstanceModel
                    ->where('owner_type', 'page')
                    ->where('owner_id', $pageId)
                    ->where('block_id', (int) $blockType->id)
                    ->where('sort_order', $sortOrder)
                    ->where('parent_instance_id IS NULL', null, false)
                    ->first();

                $payload = [
                    'block_id'           => (int) $blockType->id,
                    'owner_type'         => 'page',
                    'owner_id'           => $pageId,
                    'parent_instance_id' => null,
                    'sort_order'         => $sortOrder,
                    'column_index'       => null,
                    'is_active'          => 1,
                    'block_config'       => json_encode($blockDef['block_config_defaults'] ?? new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];

                if ($existing !== null && isset($existing->id)) {
                    $instanceId = (int) $existing->id;
                    $blockInstanceModel->update($instanceId, $payload);
                } else {
                    $instanceId = (int) $blockInstanceModel->insert(array_merge($payload, [
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]));
                }

                foreach ($activeLanguages as $language) {
                    if (! $language instanceof \App\Entities\LanguageEntity) {
                        continue;
                    }

                    $existingTranslation = $translationModel
                        ->where('instance_id', $instanceId)
                        ->where('language_id', (int) $language->id)
                        ->first();

                    $translationPayload = [
                        'instance_id'  => $instanceId,
                        'language_id'  => (int) $language->id,
                        'block_data'   => json_encode(new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'is_published' => 1,
                    ];

                    if ($existingTranslation !== null && isset($existingTranslation->id)) {
                        $translationModel->update((int) $existingTranslation->id, $translationPayload);
                    } else {
                        $translationModel->insert($translationPayload);
                    }
                }
            }

            $db->transComplete();

            if (! $db->transStatus()) {
                throw new \RuntimeException('Page preset initialization transaction failed');
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
