<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use App\DTO\Editor\EditorDocumentPatchRequestDTO;
use App\DTO\Editor\EditorDocumentResponseDTO;
use Config\Editor;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

/** Builds and validates the final graph before any operation can write to the database. */
final class EditorPatchPlanner
{
    public function __construct(private readonly Editor $limits, private readonly EditorFieldValidator $fields)
    {
    }

    /** @return array<string, array<string, mixed>> keyed by persistent or temporary reference */
    public function plan(EditorDocumentResponseDTO $document, EditorDocumentPatchRequestDTO $patch): array
    {
        $nodes = [];
        foreach ($document->blocks as $block) {
            $block['parent_ref'] = $block['parent_instance_id'] === null ? null : 'id_' . $block['parent_instance_id'];
            $nodes['id_' . $block['instance_id']] = $block;
        }
        $catalog = array_column($document->catalog, null, 'block_key');
        $locales = array_column($document->locales, 'code');
        $seen = [];
        foreach ($patch->ops as $op) {
            if ($op['op'] !== 'upsert') {
                continue;
            }
            $ref = $this->reference($op);
            if (isset($seen[$ref])) {
                $this->invalid();
            }
            $seen[$ref] = true;
            $nodes[$ref] = $this->upsert($nodes[$ref] ?? null, $op, $catalog, $locales);
        }
        foreach ($patch->ops as $op) {
            if ($op['op'] === 'delete') {
                $this->onlyKeys($op, ['op', 'instance_id', 'temp_id']);
                $ref = $this->reference($op);
                if (! isset($nodes[$ref]) || isset($seen[$ref])) {
                    $this->invalid();
                }
                $this->remove($nodes, $ref);
                $seen[$ref] = true;
            }
        }
        $reordered = [];
        foreach ($patch->ops as $op) {
            if ($op['op'] !== 'reorder') {
                continue;
            }
            $this->onlyKeys($op, ['op', 'items']);
            $items = $op['items'] ?? null;
            if (! is_array($items) || ! array_is_list($items) || $items === [] || count($items) > $this->limits->maxBlocks) {
                $this->invalid();
            }
            foreach ($items as $item) {
                if (! is_array($item)) {
                    $this->invalid();
                }
                $this->onlyKeys($item, ['instance_id', 'temp_id', 'parent_instance_id', 'parent_temp_id', 'sort_order']);
                $ref = $this->reference($item);
                if (! isset($nodes[$ref]) || isset($reordered[$ref])) {
                    $this->invalid();
                }
                $nodes[$ref]['sort_order'] = $this->sortOrder($item['sort_order'] ?? null);
                $nodes[$ref]['parent_ref'] = $this->parent($item, $nodes[$ref]['parent_ref']);
                $reordered[$ref] = true;
            }
        }
        if (count($nodes) > $this->limits->maxBlocks) {
            $this->invalid();
        }
        $this->validateTree($nodes, $catalog);
        uasort($nodes, static fn (array $a, array $b): int => [$a['sort_order'], $a['instance_id'] ?? $a['temp_id']] <=> [$b['sort_order'], $b['instance_id'] ?? $b['temp_id']]);
        $positions = [];
        foreach ($nodes as &$node) {
            $parent = $node['parent_ref'] ?? 'root';
            $positions[$parent] = ($positions[$parent] ?? 0) + 1;
            $node['sort_order'] = $positions[$parent];
        }
        unset($node);

        return $nodes;
    }

    /**
     * @param array<string, mixed>|null $node
     * @param array<string, mixed> $op
     * @param array<string, array<string, mixed>> $catalog
     * @param list<string> $locales
     * @return array<string, mixed>
     */
    private function upsert(?array $node, array $op, array $catalog, array $locales): array
    {
        $this->onlyKeys($op, ['op', 'instance_id', 'temp_id', 'block_key', 'parent_instance_id', 'parent_temp_id', 'sort_order', 'config', 'i18n', 'is_active']);
        if ($node === null) {
            if (isset($op['instance_id']) || ! is_string($op['block_key'] ?? null) || ! isset($catalog[$op['block_key']])) {
                $this->invalid();
            }
            $type = $catalog[$op['block_key']];
            $node = [
                'instance_id' => null, 'temp_id' => $op['temp_id'], 'block_id' => $type['block_id'], 'block_key' => $op['block_key'],
                'parent_instance_id' => null, 'parent_ref' => null, 'column_index' => null, 'sort_order' => 1, 'is_active' => true,
                'config' => SchemaDefaults::applyConfigDefaults(['config_fields' => $type['config_fields']], []),
                'i18n' => array_fill_keys($locales, []), 'locked' => false, 'required' => false,
            ];
        }
        if (isset($op['block_key']) && $op['block_key'] !== $node['block_key']) {
            $this->invalid();
        }
        $type = $catalog[$node['block_key']] ?? null;
        if (! is_array($type)) {
            throw new ValidationException(lang('Editor.invalidField'));
        }
        $node['parent_ref'] = $this->parent($op, $node['parent_ref']);
        if (array_key_exists('sort_order', $op)) {
            $node['sort_order'] = $this->sortOrder($op['sort_order']);
        }
        if (array_key_exists('is_active', $op)) {
            if (! is_bool($op['is_active'])) {
                $this->invalid();
            }
            $node['is_active'] = $op['is_active'];
        }
        if (array_key_exists('config', $op)) {
            $changes = $this->object($op['config']);
            $this->fields->validate($changes, $type['config_fields']);
            $node['config'] = array_replace($node['config'], $changes);
        }
        if (array_key_exists('i18n', $op)) {
            foreach ($this->object($op['i18n']) as $locale => $raw) {
                if (! in_array($locale, $locales, true)) {
                    $this->invalid();
                }
                $changes = $this->object($raw);
                $this->fields->validate($changes, $type['fields']);
                $node['i18n'][$locale] = array_replace($node['i18n'][$locale] ?? [], $changes);
            }
        }

        return $node;
    }

    /** @param array<string, array<string, mixed>> $nodes */
    private function remove(array &$nodes, string $ref): void
    {
        $pending = [$ref];
        $remove = [];
        while ($pending !== []) {
            $current = array_pop($pending);
            if (isset($remove[$current])) {
                continue;
            }
            $node = $nodes[$current];
            if ($node['locked'] || $node['required']) {
                throw new ValidationException(lang('Editor.protectedBlock'));
            }
            $remove[$current] = true;
            foreach ($nodes as $key => $child) {
                if ($child['parent_ref'] === $current) {
                    $pending[] = $key;
                }
            }
        }
        foreach ($remove as $key => $_) {
            unset($nodes[$key]);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $nodes
     * @param array<string, array<string, mixed>> $catalog
     */
    private function validateTree(array $nodes, array $catalog): void
    {
        foreach ($nodes as $ref => $node) {
            $parent = $node['parent_ref'];
            if ($parent !== null) {
                $parentNode = $nodes[$parent] ?? null;
                $parentType = $catalog[$parentNode['block_key'] ?? ''] ?? null;
                if ($parentNode === null || ! ($parentType['is_container'] ?? false)
                    || ! in_array($node['block_key'], $parentType['allowed_children'] ?? [], true)) {
                    $this->invalid();
                }
            }
            $visited = [$ref => true];
            while ($parent !== null) {
                if (isset($visited[$parent]) || count($visited) >= $this->limits->maxDepth || ! isset($nodes[$parent])) {
                    $this->invalid();
                }
                $visited[$parent] = true;
                $parent = $nodes[$parent]['parent_ref'];
            }
        }
    }

    /** @param array<string, mixed> $op */
    private function reference(array $op): string
    {
        $id = $op['instance_id'] ?? null;
        $temp = $op['temp_id'] ?? null;
        if (is_int($id) && $id > 0 && $temp === null) {
            return 'id_' . $id;
        }
        if ($id === null && is_string($temp) && preg_match('/^tmp_[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D', $temp) === 1) {
            return $temp;
        }
        $this->invalid();
    }

    /** @param array<string, mixed> $op */
    private function parent(array $op, ?string $current): ?string
    {
        if (array_key_exists('parent_instance_id', $op) && array_key_exists('parent_temp_id', $op)) {
            $this->invalid();
        }
        if (array_key_exists('parent_instance_id', $op)) {
            return $op['parent_instance_id'] === null ? null : $this->reference(['instance_id' => $op['parent_instance_id']]);
        }
        if (array_key_exists('parent_temp_id', $op)) {
            return $this->reference(['temp_id' => $op['parent_temp_id']]);
        }

        return $current;
    }

    private function sortOrder(mixed $value): int
    {
        if (! is_int($value) || $value < 0 || $value > $this->limits->maxBlocks) {
            $this->invalid();
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            $this->invalid();
        }
        foreach (array_keys($value) as $key) {
            if (! is_string($key)) {
                $this->invalid();
            }
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $op
     * @param list<string> $allowed
     */
    private function onlyKeys(array $op, array $allowed): void
    {
        if (array_diff(array_keys($op), $allowed) !== []) {
            $this->invalid();
        }
    }

    private function invalid(): never
    {
        throw new ValidationException(lang('Editor.invalidTree'));
    }
}
