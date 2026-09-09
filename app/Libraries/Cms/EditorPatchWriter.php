<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use App\DTO\Editor\EditorOwnerDTO;
use App\DTO\Request\Cms\BlockInstanceCreateRequestDTO;
use App\DTO\Request\Cms\BlockInstanceUpdateRequestDTO;
use App\Interfaces\Cms\BlockInstanceServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Support\JsonCastNormalizer;
use dcardenasl\Ci4ApiCore\Support\RequestDtoFactory;

/** Applies an already validated plan exclusively through the existing block service. */
final class EditorPatchWriter
{
    public function __construct(private readonly BlockInstanceServiceInterface $blocks, private readonly RequestDtoFactory $requests)
    {
    }

    /**
     * @param array<string, array<string, mixed>> $nodes
     * @return array<string, int>
     */
    public function apply(EditorOwnerDTO $owner, EditorDocumentSnapshot $snapshot, array $nodes, SecurityContext $context): array
    {
        $idMap = [];
        $original = array_column($snapshot->instances, null, 'id');
        $pending = $nodes;
        while ($pending !== []) {
            $advanced = false;
            foreach ($pending as $ref => $node) {
                $parentRef = $node['parent_ref'];
                if ($parentRef !== null && str_starts_with($parentRef, 'tmp_') && ! isset($idMap[$parentRef])) {
                    continue;
                }
                $parentId = $parentRef === null ? null : ($idMap[$parentRef] ?? (int) substr($parentRef, 3));
                $payload = $this->payload($node, $snapshot);
                $payload['parent_instance_id'] = $parentId;
                if ($node['instance_id'] === null) {
                    $payload['owner_type'] = $owner->type;
                    $payload['owner_id'] = $owner->id;
                    $request = $this->requests->make(BlockInstanceCreateRequestDTO::class, $payload);
                    $created = $this->blocks->store($request, $context)->toArray();
                    $id = $created['id'] ?? null;
                    if (! is_int($id) || $id < 1) {
                        throw new \UnexpectedValueException('The block service did not return its created ID.');
                    }
                    $idMap[$ref] = $id;
                } else {
                    $id = (int) $node['instance_id'];
                    if ($this->hasChanges($payload, $original[$id], $snapshot)) {
                        $this->blocks->setOwnerContext($owner->type, $owner->id);
                        $this->blocks->update($id, $this->requests->make(BlockInstanceUpdateRequestDTO::class, $payload), $context);
                    }
                }
                unset($pending[$ref]);
                $advanced = true;
            }
            if (! $advanced) {
                throw new \LogicException('A validated editor plan must have acyclic temporary parents.');
            }
        }
        // Delete children first. This preserves the service's checks and audit per
        // instance instead of relying on unaudited foreign-key cascade side effects.
        $deleted = array_filter($original, static fn (array $row): bool => ! isset($nodes['id_' . $row['id']]));
        while ($deleted !== []) {
            $advanced = false;
            foreach ($deleted as $id => $row) {
                $hasChild = false;
                foreach ($deleted as $child) {
                    if (isset($child['parent_instance_id']) && (int) $child['parent_instance_id'] === $id) {
                        $hasChild = true;
                        break;
                    }
                }
                if ($hasChild) {
                    continue;
                }
                $this->blocks->setOwnerContext($owner->type, $owner->id);
                $this->blocks->destroy($id, $context);
                unset($deleted[$id]);
                $advanced = true;
            }
            if (! $advanced) {
                throw new \LogicException('Cannot delete a cyclic persisted block tree.');
            }
        }

        return $idMap;
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private function payload(array $node, EditorDocumentSnapshot $snapshot): array
    {
        $flags = [];
        foreach ($snapshot->translations as $row) {
            if ((int) $row['instance_id'] === $node['instance_id']) {
                $flags[(int) $row['language_id']] = (bool) $row['is_published'];
            }
        }
        $translations = [];
        foreach ($snapshot->languages as $language) {
            if (! (bool) $language['is_active']) {
                continue; // TranslationTableSynchronizer preserves inactive language history.
            }
            $data = $node['i18n'][(string) $language['code']] ?? [];
            $languageId = (int) $language['id'];
            if ($data === [] && ! array_key_exists($languageId, $flags)) {
                continue; // An untouched, missing language must remain absent.
            }
            $translations[] = ['language_id' => $languageId, 'block_data' => $data, 'is_published' => $flags[$languageId] ?? true];
        }

        $payload = [
            'block_id' => $node['block_id'], 'sort_order' => $node['sort_order'], 'column_index' => $node['column_index'],
            'is_active' => $node['is_active'], 'block_config' => $node['config'],
        ];
        // CreateRequestDTO's wildcard validation rejects translations: [];
        // omit that optional field for a new, untranslated block.
        if ($translations !== [] || $node['instance_id'] !== null) {
            $payload['translations'] = $translations;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $existing
     */
    private function hasChanges(array $payload, array $existing, EditorDocumentSnapshot $snapshot): bool
    {
        foreach (['block_id', 'sort_order', 'column_index', 'parent_instance_id'] as $key) {
            $old = isset($existing[$key]) ? (int) $existing[$key] : null;
            if ($payload[$key] !== $old) {
                return true;
            }
        }
        if ($payload['is_active'] !== (bool) $existing['is_active']
            || $payload['block_config'] !== JsonCastNormalizer::toArray($existing['block_config'])) {
            return true;
        }
        $oldTranslations = [];
        $active = array_column(array_filter($snapshot->languages, static fn (array $row): bool => (bool) $row['is_active']), 'id');
        foreach ($snapshot->translations as $row) {
            if ((int) $row['instance_id'] === (int) $existing['id'] && in_array($row['language_id'], $active, true)) {
                $oldTranslations[(int) $row['language_id']] = ['language_id' => (int) $row['language_id'], 'block_data' => JsonCastNormalizer::toArray($row['block_data']), 'is_published' => (bool) $row['is_published']];
            }
        }
        $newTranslations = array_column($payload['translations'] ?? [], null, 'language_id');

        return $oldTranslations !== $newTranslations;
    }
}
