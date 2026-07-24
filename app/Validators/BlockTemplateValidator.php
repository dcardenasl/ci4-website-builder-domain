<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\BlockTemplateValidationException;
use App\Libraries\Cms\BlockTemplateNormalizer;

class BlockTemplateValidator
{
    /**
     * Validates a decoded block_template array against schema and business rules.
     * Passes silently when $template is null (field is optional).
     *
     * @param array<string, mixed>|null $template
     * @throws BlockTemplateValidationException
     */
    public function validate(?array $template): void
    {
        if ($template === null) {
            return;
        }

        $normalized = BlockTemplateNormalizer::normalize($template);
        if ($normalized === null) {
            return;
        }

        $this->validateBlockKeysExist((array) ($normalized['blocks'] ?? []));
    }

    /**
     * @param array<int, mixed> $blocks
     * @throws BlockTemplateValidationException
     */
    private function validateBlockKeysExist(array $blocks): void
    {
        /** @var \App\Models\BlockTypeModel $blockTypeModel */
        $blockTypeModel = model(\App\Models\BlockTypeModel::class);

        $validKeys = $blockTypeModel
            ->select('block_key')
            ->where('is_active', 1)
            ->findAll();

        /** @var array<int, string> $validKeySet */
        $validKeySet = array_column(
            array_map(fn ($bt) => $bt instanceof \App\Entities\BlockTypeEntity ? $bt->toArray() : (array) $bt, $validKeys),
            'block_key'
        );

        foreach ($blocks as $index => $block) {
            if (! is_array($block)) {
                continue;
            }

            $blockKey = (string) ($block['block_key'] ?? '');
            if (! in_array($blockKey, $validKeySet, true)) {
                throw new BlockTemplateValidationException(
                    "Block at index {$index}: block_key '{$blockKey}' does not match any active block type"
                );
            }
        }
    }
}
