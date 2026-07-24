<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\BlockTypeEntity;
use App\Entities\CollectionEntity;
use App\Entities\LanguageEntity;
use App\Entities\MenuEntity;
use App\Entities\PageEntity;
use App\Entities\PageTranslationEntity;
use App\Interfaces\Cms\WizardConfigServiceInterface;
use App\Libraries\Cms\BlockSchemaIntrospector;
use App\Libraries\Cms\CmsEnums;
use App\Libraries\Cms\FieldPrimitiveRegistry;
use App\Libraries\Cms\JsonCastNormalizer;
use App\Libraries\Cms\OwnerUsageResolver;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

/**
 * Aggregates languages, collections, pages, menus, and block type schemas
 * for the admin creation wizard (screen B3 and friends).
 *
 * Extracted from WizardConfigController::config(), which used to resolve 8
 * models inline and reimplement "resolve name by default-language" for
 * collections and menus independently (and with no language filter at all
 * for collections — a latent bug, first translation row found regardless of
 * language). Both are unified here via OwnerUsageResolver::resolveTitles(),
 * the same batch-title resolver already used by FormService/BlockTypeService's
 * usage reports (ARCH-DEEP-01). Pages keep their own batch lookup because
 * they need both title AND slug, which resolveTitles() does not provide.
 */
class WizardConfigService implements WizardConfigServiceInterface
{
    /** @var list<BlockTypeEntity>|null */
    private ?array $activeBlockTypesCache = null;

    /**
     * @param RepositoryInterface<LanguageEntity> $languageRepository
     * @param RepositoryInterface<CollectionEntity> $collectionRepository
     * @param RepositoryInterface<PageEntity> $pageRepository
     * @param RepositoryInterface<PageTranslationEntity> $pageTranslationRepository
     * @param RepositoryInterface<MenuEntity> $menuRepository
     * @param RepositoryInterface<BlockTypeEntity> $blockTypeRepository
     */
    public function __construct(
        private readonly RepositoryInterface $languageRepository,
        private readonly RepositoryInterface $collectionRepository,
        private readonly RepositoryInterface $pageRepository,
        private readonly RepositoryInterface $pageTranslationRepository,
        private readonly RepositoryInterface $menuRepository,
        private readonly RepositoryInterface $blockTypeRepository,
        private readonly OwnerUsageResolver $ownerUsageResolver,
        private readonly BlockSchemaIntrospector $blockSchemaIntrospector,
        private readonly FieldPrimitiveRegistry $fieldPrimitiveRegistry,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildConfig(): array
    {
        $languagesData = $this->buildLanguagesData();
        $defaultLanguage = $this->resolveDefaultLanguage($languagesData);

        /** @var list<CollectionEntity> $collections */
        $collections = $this->collectionRepository->getModel()
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        /** @var list<PageEntity> $pages */
        $pages = $this->pageRepository->getModel()
            ->select('id, sort_order')
            ->where('status !=', 'archived')
            ->orderBy('sort_order', 'ASC')
            ->limit(50)
            ->findAll();

        /** @var list<MenuEntity> $menus */
        $menus = $this->menuRepository->getModel()
            ->where('is_active', 1)
            ->findAll();

        $titles = $this->ownerUsageResolver->resolveTitles(
            [
                ...array_map(static fn (CollectionEntity $c): array => ['owner_type' => 'collection', 'owner_id' => (int) $c->id], $collections),
                ...array_map(static fn (MenuEntity $m): array => ['owner_type' => 'menu', 'owner_id' => (int) $m->id], $menus),
            ],
            $defaultLanguage['code'] !== '' ? $defaultLanguage['code'] : null
        );

        [$blockTypesMap, $blockCapabilities] = $this->buildBlockTypesData();

        return [
            'languages'              => $languagesData,
            'default_language_id'    => $defaultLanguage['id'],
            'collections'            => $this->buildCollectionsData($collections, $titles),
            'pages'                  => $this->buildPagesData($pages, $defaultLanguage['id']),
            'menus'                  => $this->buildMenusData($menus, $titles),
            'block_types'            => $blockTypesMap,
            'non_translatable_types' => CmsEnums::NON_TRANSLATABLE_TYPES,
            'field_primitives'       => $this->fieldPrimitiveRegistry->supported(),
            'block_capabilities'     => $blockCapabilities,
            'setup_state'            => [
                'has_languages'          => $languagesData !== [],
                'has_collections'        => $collections !== [],
                'has_active_block_types' => $this->activeBlockTypes() !== [],
            ],
        ];
    }

    /**
     * @return list<array{id: int, code: string, name: string, native_name: string, is_default: bool}>
     */
    private function buildLanguagesData(): array
    {
        /** @var list<LanguageEntity> $languages */
        $languages = $this->languageRepository->getModel()
            ->select('id, code, name, native_name, is_default')
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        return array_map(static fn (LanguageEntity $language): array => [
            'id'          => (int) $language->id,
            'code'        => (string) $language->code,
            'name'        => (string) $language->name,
            'native_name' => (string) $language->native_name,
            'is_default'  => (bool) $language->is_default,
        ], $languages);
    }

    /**
     * @param list<array{id: int, code: string, name: string, native_name: string, is_default: bool}> $languagesData
     * @return array{id: int, code: string}
     */
    private function resolveDefaultLanguage(array $languagesData): array
    {
        foreach ($languagesData as $language) {
            if ($language['is_default']) {
                return ['id' => $language['id'], 'code' => $language['code']];
            }
        }

        foreach ($languagesData as $language) {
            return ['id' => $language['id'], 'code' => $language['code']];
        }

        return ['id' => 1, 'code' => ''];
    }

    /**
     * @param list<CollectionEntity> $collections
     * @param array<string, string> $titles
     * @return list<array<string, mixed>>
     */
    private function buildCollectionsData(array $collections, array $titles): array
    {
        return array_map(static function (CollectionEntity $collection) use ($titles): array {
            $wizardConfig = $collection->wizard_config !== null ? JsonCastNormalizer::toArray($collection->wizard_config) : null;
            $blockTemplate = $collection->block_template !== null ? JsonCastNormalizer::toArray($collection->block_template) : null;
            $name = $titles['collection:' . $collection->id] ?? (string) $collection->collection_key;

            return [
                'id'             => (int) $collection->id,
                'collection_key' => (string) $collection->collection_key,
                'name'           => $name,
                'icon'           => $wizardConfig['icon'] ?? '📄',
                'description'    => $wizardConfig['description'] ?? '',
                'wizard_config'  => $wizardConfig,
                'block_template' => $blockTemplate,
            ];
        }, $collections);
    }

    /**
     * @param list<PageEntity> $pages
     * @return list<array{id: int, title: string, slug: string}>
     */
    private function buildPagesData(array $pages, int $defaultLanguageId): array
    {
        $pageIds = array_map(static fn (PageEntity $page): int => (int) $page->id, $pages);

        /** @var array<int, array{title: string, slug: string}> $infoByPage */
        $infoByPage = [];
        if ($pageIds !== []) {
            /** @var list<PageTranslationEntity> $translations */
            $translations = $this->pageTranslationRepository->getModel()
                ->select('page_id, title, slug')
                ->whereIn('page_id', $pageIds)
                ->where('language_id', $defaultLanguageId)
                ->findAll();

            foreach ($translations as $translation) {
                $pageId = (int) $translation->page_id;
                if (!isset($infoByPage[$pageId])) {
                    $infoByPage[$pageId] = [
                        'title' => (string) ($translation->title ?? ''),
                        'slug'  => (string) ($translation->slug ?? ''),
                    ];
                }
            }
        }

        return array_map(static function (PageEntity $page) use ($infoByPage): array {
            $pageId = (int) $page->id;
            $info = $infoByPage[$pageId] ?? ['title' => 'Page #' . $pageId, 'slug' => ''];

            return ['id' => $pageId, 'title' => $info['title'], 'slug' => $info['slug']];
        }, $pages);
    }

    /**
     * @param list<MenuEntity> $menus
     * @param array<string, string> $titles
     * @return list<array{id: int, menu_key: string, name: string}>
     */
    private function buildMenusData(array $menus, array $titles): array
    {
        return array_map(static fn (MenuEntity $menu): array => [
            'id'       => (int) $menu->id,
            'menu_key' => (string) $menu->menu_key,
            'name'     => $titles['menu:' . $menu->id] ?? (string) $menu->menu_key,
        ], $menus);
    }

    /**
     * Keyed by block_key so the wizard can look up field labels and types
     * when rendering the block editor (screen B3). Returns [block_types map,
     * block_capabilities map] from a single pass over the active block types
     * (one schema introspection per type, shared by both maps).
     *
     * @return array{0: array<string, array<string, mixed>>, 1: array<string, array<string, mixed>>}
     */
    private function buildBlockTypesData(): array
    {
        $blockTypesMap = [];
        $blockCapabilities = [];

        foreach ($this->activeBlockTypes() as $blockType) {
            $blockKey = (string) $blockType->block_key;
            if ($blockKey === '') {
                continue;
            }

            // introspect() self-normalizes, but we also need config_fields
            // straight off the schema, so normalize once and reuse.
            $schema = JsonCastNormalizer::toArray($blockType->schema_definition);
            $capabilities = $this->blockSchemaIntrospector->introspect($schema);
            $blockCapabilities[$blockKey] = $capabilities;

            $blockTypesMap[$blockKey] = [
                'id'               => (int) $blockType->id,
                'name'             => (string) ($blockType->name ?? $blockKey),
                'description'      => $blockType->description ?? null,
                'icon'             => $blockType->icon ?? null,
                'fields'           => $capabilities['fields'],
                'config_fields'    => (array) ($schema['config_fields'] ?? []),
                'capabilities'     => $capabilities,
                'supports_pages'   => (bool) $blockType->supports_pages,
                'supports_entries' => (bool) $blockType->supports_entries,
                'is_container'     => (bool) $blockType->is_container,
                'is_active'        => (bool) $blockType->is_active,
                'sort_order'       => (int) $blockType->sort_order,
            ];
        }

        return [$blockTypesMap, $blockCapabilities];
    }

    /**
     * @return list<BlockTypeEntity>
     */
    private function activeBlockTypes(): array
    {
        if ($this->activeBlockTypesCache === null) {
            /** @var list<BlockTypeEntity> $blockTypes */
            $blockTypes = $this->blockTypeRepository->getModel()
                ->select('id, block_key, name, description, icon, schema_definition, supports_pages, supports_entries, is_container, is_active, sort_order')
                ->where('is_active', 1)
                ->findAll();

            $this->activeBlockTypesCache = $blockTypes;
        }

        return $this->activeBlockTypesCache;
    }
}
