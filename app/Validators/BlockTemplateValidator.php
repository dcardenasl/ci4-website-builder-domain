<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\BlockTemplateValidationException;

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

        $this->validateStructure($template);
        $this->validateBlocks((array) ($template['blocks'] ?? []));
        $this->validateBlockKeysExist((array) ($template['blocks'] ?? []));
    }

    /**
     * @param array<string, mixed> $template
     * @throws BlockTemplateValidationException
     */
    private function validateStructure(array $template): void
    {
        if (!isset($template['version'])) {
            throw new BlockTemplateValidationException('Missing required field: version');
        }

        if ($template['version'] !== '1.0') {
            throw new BlockTemplateValidationException('version must be "1.0"');
        }

        if (!isset($template['blocks']) || !is_array($template['blocks'])) {
            throw new BlockTemplateValidationException('blocks must be an array');
        }

        if (count($template['blocks']) === 0) {
            throw new BlockTemplateValidationException('blocks array must have at least one item');
        }

        if (count($template['blocks']) > 50) {
            throw new BlockTemplateValidationException('blocks array must have at most 50 items');
        }
    }

    /**
     * @param array<int, mixed> $blocks
     * @throws BlockTemplateValidationException
     */
    private function validateBlocks(array $blocks): void
    {
        $sortOrders = [];

        foreach ($blocks as $index => $block) {
            if (!is_array($block)) {
                throw new BlockTemplateValidationException("Block at index {$index} must be an object");
            }

            $blockKey = $block['block_key'] ?? null;
            if (!is_string($blockKey) || $blockKey === '') {
                throw new BlockTemplateValidationException("Block at index {$index}: block_key is required and must be a string");
            }

            if (!preg_match('/^[a-z][a-z0-9_]*$/', $blockKey)) {
                throw new BlockTemplateValidationException("Block at index {$index}: block_key must match ^[a-z][a-z0-9_]*$");
            }

            if (strlen($blockKey) > 50) {
                throw new BlockTemplateValidationException("Block at index {$index}: block_key must not exceed 50 characters");
            }

            $sortOrder = $block['sort_order'] ?? null;
            if (!is_int($sortOrder)) {
                throw new BlockTemplateValidationException("Block at index {$index}: sort_order must be an integer");
            }

            if ($sortOrder < 1 || $sortOrder > 1000) {
                throw new BlockTemplateValidationException("Block at index {$index}: sort_order must be between 1 and 1000");
            }

            if (in_array($sortOrder, $sortOrders, true)) {
                throw new BlockTemplateValidationException("Duplicate sort_order {$sortOrder}: each block must have a unique sort_order");
            }

            $sortOrders[] = $sortOrder;

            if (isset($block['label']) && (!is_string($block['label']) || strlen($block['label']) > 100)) {
                throw new BlockTemplateValidationException("Block at index {$index}: label must be a string with at most 100 characters");
            }

            if (isset($block['help_text']) && (!is_string($block['help_text']) || strlen($block['help_text']) > 500)) {
                throw new BlockTemplateValidationException("Block at index {$index}: help_text must be a string with at most 500 characters");
            }

            if (isset($block['block_config_defaults']) && !is_array($block['block_config_defaults'])) {
                throw new BlockTemplateValidationException("Block at index {$index}: block_config_defaults must be an object");
            }
        }
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
            if (!is_array($block)) {
                continue;
            }
            $blockKey = (string) ($block['block_key'] ?? '');
            if (!in_array($blockKey, $validKeySet, true)) {
                throw new BlockTemplateValidationException(
                    "Block at index {$index}: block_key '{$blockKey}' does not match any active block type"
                );
            }
        }
    }
}
