<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\PageEntity;
use App\Interfaces\Cms\PageServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<PageEntity>
 */
class PageService extends BaseCrudService implements PageServiceInterface
{
    /** @var array<mixed>|null */
    private ?array $tempTranslations = null;

    private \App\Libraries\Cms\SlugRedirectRecorder $slugRedirectRecorder;

    /**
     * @param RepositoryInterface<PageEntity> $pageRepository
     */
    public function __construct(
        RepositoryInterface $pageRepository,
        ResponseMapperInterface $responseMapper,
        ?\App\Libraries\Cms\SlugRedirectRecorder $slugRedirectRecorder = null
    ) {
        parent::__construct($pageRepository, $responseMapper);
        $this->slugRedirectRecorder = $slugRedirectRecorder ?? service('slugRedirectRecorder');
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);

        $parentId = isset($data['parent_id']) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
        if ($parentId !== null) {
            $parent = $this->repository->find($parentId);
            if (!$parent) {
                throw new ValidationException(
                    lang('Pages.invalid_hierarchy'),
                    ['parent_id' => lang('Pages.parent_not_exists')]
                );
            }
        }

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
        $this->createVersionSnapshot((int) $entity->id, 'Initial creation');
        $this->tempTranslations = null;
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);

        if (array_key_exists('parent_id', $data)) {
            $parentId = $data['parent_id'] !== null && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
            $this->validateParent($id, $parentId);
        }

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
        $this->createVersionSnapshot((int) $entity->id, 'Update page');
        $this->tempTranslations = null;
    }

    protected function enrichEntities(array $entities): array
    {
        if (empty($entities)) {
            return $entities;
        }

        $pageIds = array_map(fn ($entity) => (int) $entity->id, $entities);

        /** @var \App\Models\PageTranslationModel $translationModel */
        $translationModel = model(\App\Models\PageTranslationModel::class);
        $translations = $translationModel->whereIn('page_id', $pageIds)->findAll();

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            /** @var \App\Entities\PageTranslationEntity $translation */
            $translationsGrouped[$translation->page_id][] = [
                'language_id'      => (int) $translation->language_id,
                'slug'             => $translation->slug,
                'title'            => $translation->title,
                'excerpt'          => $translation->excerpt,
                'meta_title'       => $translation->meta_title,
                'meta_description' => $translation->meta_description,
                'og_image_file_id' => $translation->og_image_file_id,
                'og_type'          => $translation->og_type,
                'canonical_url'    => $translation->canonical_url,
                'robots'           => $translation->robots,
                'schema_data'      => $translation->schema_data,
            ];
        }

        foreach ($entities as $entity) {
            $entity->translations = $translationsGrouped[$entity->id] ?? [];
        }

        return $entities;
    }

    /**
     * @param array<mixed> $translations
     */
    private function saveTranslations(int $pageId, array $translations): void
    {
        /** @var \App\Models\PageTranslationModel $translationModel */
        $translationModel = model(\App\Models\PageTranslationModel::class);

        foreach ($translations as $translation) {
            $langId = (int) $translation['language_id'];
            $slug = (string) $translation['slug'];

            $existing = $translationModel
                ->where('language_id', $langId)
                ->where('slug', $slug)
                ->where('page_id !=', $pageId)
                ->first();

            if ($existing) {
                throw new ValidationException(
                    lang('Pages.slug_must_be_unique'),
                    ['slug' => lang('Pages.slug_already_taken', [$slug])]
                );
            }
        }

        // Query current translations to compare slugs
        $currentTranslations = $translationModel->where('page_id', $pageId)->findAll();
        $currentSlugs = [];
        foreach ($currentTranslations as $ct) {
            if ($ct instanceof \App\Entities\PageTranslationEntity) {
                $currentSlugs[(int)$ct->language_id] = $ct->slug;
            }
        }

        $translationModel->where('page_id', $pageId)->delete();

        foreach ($translations as $translation) {
            $langId = (int) $translation['language_id'];
            $newSlug = (string) $translation['slug'];

            // Record redirection if slug changed
            if (isset($currentSlugs[$langId]) && $currentSlugs[$langId] !== $newSlug) {
                // Determine old path for page (simple slug for now or we could load path logic)
                // Since parent could have slugs, let's look at the old slug itself
                $this->slugRedirectRecorder->record('page', $pageId, $langId, $currentSlugs[$langId], $newSlug, $currentSlugs[$langId]);
            }

            $translationModel->insert([
                'page_id'          => $pageId,
                'language_id'      => $langId,
                'slug'             => $newSlug,
                'title'            => $translation['title'],
                'excerpt'          => $translation['excerpt'] ?? null,
                'meta_title'       => $translation['meta_title'] ?? null,
                'meta_description' => $translation['meta_description'] ?? null,
                'og_image_file_id' => isset($translation['og_image_file_id']) && $translation['og_image_file_id'] !== '' ? (int) $translation['og_image_file_id'] : null,
                'og_type'          => $translation['og_type'] ?? null,
                'canonical_url'    => $translation['canonical_url'] ?? null,
                'robots'           => $translation['robots'] ?? null,
                'schema_data'      => isset($translation['schema_data']) ? json_encode($translation['schema_data']) : null,
            ]);
        }
    }

    public function createVersionSnapshot(int $pageId, string $note = ''): void
    {
        $page = $this->repository->find($pageId);
        if (!$page) {
            return;
        }

        /** @var \App\Models\PageTranslationModel $translationModel */
        $translationModel = model(\App\Models\PageTranslationModel::class);
        $translations = $translationModel->where('page_id', $pageId)->findAll();

        $translationsData = [];
        foreach ($translations as $t) {
            if ($t instanceof \CodeIgniter\Entity\Entity) {
                $translationsData[] = $t->toArray();
            }
        }

        $snapshot = [
            'page'         => $page->toArray(),
            'translations' => $translationsData,
        ];

        /** @var \App\Models\PageVersionModel $versionModel */
        $versionModel = model(\App\Models\PageVersionModel::class);
        $lastVersion = $versionModel->where('page_id', $pageId)
            ->orderBy('version_number', 'DESC')
            ->first();

        $nextVersionNumber = 1;
        if ($lastVersion instanceof \App\Entities\PageVersionEntity) {
            $nextVersionNumber = (int) $lastVersion->version_number + 1;
        }

        $versionModel->insert([
            'page_id'        => $pageId,
            'version_number' => $nextVersionNumber,
            'snapshot'       => json_encode($snapshot),
            'note'           => $note,
        ]);
    }

    private function validateParent(int $id, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($id === $parentId) {
            throw new ValidationException(
                lang('Pages.invalid_hierarchy'),
                ['parent_id' => lang('Pages.cannot_be_own_parent')]
            );
        }

        $parent = $this->repository->find($parentId);
        if (!$parent) {
            throw new ValidationException(
                lang('Pages.invalid_hierarchy'),
                ['parent_id' => lang('Pages.parent_not_exists')]
            );
        }

        $currentParentId = $parent->parent_id;
        while ($currentParentId !== null) {
            if ((int) $currentParentId === $id) {
                throw new ValidationException(
                    lang('Pages.invalid_hierarchy'),
                    ['parent_id' => lang('Pages.circular_reference')]
                );
            }

            $ancestor = $this->repository->find($currentParentId);
            $currentParentId = $ancestor ? $ancestor->parent_id : null;
        }
    }
}
