<?php

declare(strict_types=1);

namespace App\Traits\Services;

/**
 * Small composable helpers for the "extract translations from the incoming
 * payload, persist the parent entity, then persist translations" deferral
 * pattern shared by every translatable CMS resource that is NOT a taxonomy
 * (Entry, Menu, MenuItem, Page, BlockInstance, Collection, Setting —
 * taxonomies already have their own `HasTranslatableTaxonomyLifecycle`).
 *
 * Unlike `HasTranslatableTaxonomyLifecycle`, this trait does NOT own
 * `beforeStore()`/`afterStore()`/`beforeUpdate()`/`afterUpdate()` outright —
 * those 7 services each interleave unrelated logic in those hooks (version
 * snapshots, cache scopes that differ per entity, slug-uniqueness checks,
 * an `is_translatable` gate, block-config normalization, …), so forcing one
 * rigid method shape on all of them would either lose behavior or require
 * rewriting every hook's control flow. Instead, each service keeps its own
 * hook bodies and calls these three helpers at the right point — the part
 * that really was byte-identical across all 7 (2026-07-19 hygiene cleanup,
 * continuation of DEBT-001).
 *
 * Usage:
 *
 *   protected function beforeStore(array $data, ?SecurityContext $context): array
 *   {
 *       $data = parent::beforeStore($data, $context);
 *       // ... service-specific validation ...
 *       return $this->deferTranslationsFromCreate($data);
 *   }
 *
 *   protected function afterStore(object $entity, ?SecurityContext $context): void
 *   {
 *       parent::afterStore($entity, $context);
 *       $this->flushDeferredTranslations((int) $entity->id, $this->saveTranslations(...));
 *       // ... service-specific side effects ...
 *   }
 *
 * `saveTranslations()` itself stays out of this trait — each resource's
 * translation shape and validation differs too much to generalize safely.
 */
trait HasDeferredTranslations
{
    /** @var array<int, array<string, mixed>>|null */
    protected ?array $tempTranslations = null;

    /**
     * Extract `translations` from a create payload into the deferred slot.
     * Call from `beforeStore()` after any service-specific validation that
     * needs the full payload (including `translations`) has already run.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed> $data with `translations` removed
     */
    protected function deferTranslationsFromCreate(array $data): array
    {
        $this->tempTranslations = $data['translations'] ?? null;
        unset($data['translations']);

        return $data;
    }

    /**
     * Extract `translations` from an update payload into the deferred slot,
     * distinguishing "key absent" (leave existing translations untouched,
     * nothing to flush) from "key present" (replace them, even with an
     * empty array). Call from `beforeUpdate()`.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed> $data with `translations` removed
     */
    protected function deferTranslationsFromUpdate(array $data): array
    {
        if (array_key_exists('translations', $data)) {
            $this->tempTranslations = $data['translations'];
            unset($data['translations']);
        } else {
            $this->tempTranslations = null;
        }

        return $data;
    }

    /**
     * Persist the deferred translations (if any) via the given callback,
     * then always clear the deferred slot. Call from `afterStore()` /
     * `afterUpdate()` once the parent entity has an id.
     *
     * @param callable(array<int, array<string, mixed>>): void $persist
     */
    protected function flushDeferredTranslations(callable $persist, bool $shouldPersist = true): void
    {
        if ($this->tempTranslations !== null && $shouldPersist) {
            $persist($this->tempTranslations);
        }

        $this->tempTranslations = null;
    }
}
