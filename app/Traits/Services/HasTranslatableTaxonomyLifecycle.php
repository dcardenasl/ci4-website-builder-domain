<?php

declare(strict_types=1);

namespace App\Traits\Services;

use App\Libraries\Cms\CacheInvalidationClient;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;

/**
 * Shared CRUD lifecycle for CMS taxonomy resources (categories, tags): both
 * accept a `translations` array alongside store/update, persist it via each
 * resource's own saveTranslations() after the base entity is written, and
 * invalidate the same public cache scopes on every mutation. Extracted after
 * `CategoryService` and `TagService` carried byte-identical copies of these
 * five hooks (2026-07-15 hygiene cleanup, DEBT-001).
 *
 * Requires the using class to:
 *   - extend `dcardenasl\Ci4ApiCore\Services\BaseCrudService` (the `parent::`
 *     calls below resolve against whatever the using class extends)
 *   - set `$this->cacheInvalidator` in its own constructor
 *   - implement `saveTranslations()` — each resource's translation shape
 *     differs (compare CategoryService's description/meta fields against
 *     TagService's plain slug/name), so it stays out of this trait
 */
trait HasTranslatableTaxonomyLifecycle
{
    /** @var array<array<string, mixed>>|null */
    private ?array $tempTranslations = null;

    protected CacheInvalidationClient $cacheInvalidator;

    /**
     * @param array<array<string, mixed>> $translations
     */
    abstract protected function saveTranslations(int $id, array $translations): void;

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);
        $this->tempTranslations = $data['translations'] ?? null;
        unset($data['translations']);

        return $data;
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        if ($this->tempTranslations !== null) {
            $this->saveTranslations((int) $entity->id, $this->tempTranslations);
        }
        $this->cacheInvalidator->invalidate(['taxonomies', 'entries']);
        $this->tempTranslations = null;
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);
        if (array_key_exists('translations', $data)) {
            $this->tempTranslations = $data['translations'];
            unset($data['translations']);
        } else {
            $this->tempTranslations = null;
        }

        return $data;
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        if ($this->tempTranslations !== null) {
            $this->saveTranslations((int) $entity->id, $this->tempTranslations);
        }
        $this->cacheInvalidator->invalidate(['taxonomies', 'entries']);
        $this->tempTranslations = null;
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['taxonomies', 'entries']);
    }
}
