<?php

declare(strict_types=1);

namespace Config;

trait CmsDomainServices
{
    public static function resourceAuthorization(bool $getShared = true): \App\Interfaces\Cms\ResourceAuthorizationInterface
    {
        if ($getShared) {
            return static::getSharedInstance('resourceAuthorization');
        }

        return new \App\Services\Cms\ResourceAuthorizationService(
            \Config\Database::connect(),
            static::auditService(),
        );
    }

    public static function languageResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('languageResponseMapper');
        }

        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(
            \App\DTO\Response\Cms\LanguageResponseDTO::class
        );
    }

    public static function languageService(bool $getShared = true): \App\Interfaces\Cms\LanguageServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('languageService');
        }

        return new \App\Services\Cms\LanguageService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\LanguageModel::class)),
            static::languageResponseMapper()
        );
    }

    public static function settingResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('settingResponseMapper');
        }

        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(
            \App\DTO\Response\Cms\SettingResponseDTO::class
        );
    }

    public static function settingService(bool $getShared = true): \App\Interfaces\Cms\SettingServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('settingService');
        }

        return new \App\Services\Cms\SettingService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\SettingModel::class)),
            static::settingResponseMapper(),
            static::cacheInvalidationClient(),
            static::fileReferenceSynchronizer(),
            static::translationResolver(),
            static::fileUrlResolver(),
            static::publicLocaleResolver(),
            static::requestDtoFactory(),
            static::translationSynchronizer(),
            static::settingListRepository()
        );
    }

    public static function settingConnectionService(bool $getShared = true): \App\Interfaces\Cms\SettingConnectionServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('settingConnectionService');
        }

        return new \App\Services\Cms\SettingConnectionService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\SettingConnectionModel::class))
        );
    }

    public static function translationResolver(bool $getShared = true): \App\Libraries\Cms\TranslationResolver
    {
        if ($getShared) {
            return static::getSharedInstance('translationResolver');
        }

        return new \App\Libraries\Cms\TranslationResolver(static::fileUrlResolver());
    }

    /**
     * The editor writes through its own block service so cache invalidation can
     * be held back until the batch commits; the shared instance keeps announcing
     * each individual write, which would publish a half-applied document.
     */
    public static function editorCacheInvalidationClient(bool $getShared = true): \App\Libraries\Cms\EditorCacheInvalidationClient
    {
        if ($getShared) {
            return static::getSharedInstance('editorCacheInvalidationClient');
        }

        return new \App\Libraries\Cms\EditorCacheInvalidationClient();
    }

    public static function editorBlockInstanceService(bool $getShared = true): \App\Interfaces\Cms\BlockInstanceServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('editorBlockInstanceService');
        }

        return new \App\Services\Cms\BlockInstanceService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\BlockInstanceModel::class)),
            static::blockInstanceResponseMapper(),
            static::fileUrlResolver(),
            static::fileReferenceSynchronizer(),
            static::editorCacheInvalidationClient(),
            static::translationSynchronizer(),
        );
    }

    public static function editorDocumentPatchService(bool $getShared = true): \App\Interfaces\Editor\EditorDocumentPatchServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('editorDocumentPatchService');
        }

        $db = \Config\Database::connect();

        return new \App\Services\Editor\EditorDocumentPatchService(
            $db,
            new \App\Libraries\Cms\EditorDocumentSnapshotReader($db),
            new \App\Libraries\Cms\EditorDocumentAssembler(static::fileUrlResolver()),
            new \App\Libraries\Cms\EditorPatchPlanner(config(\Config\Editor::class), new \App\Libraries\Cms\EditorFieldValidator()),
            new \App\Libraries\Cms\EditorPatchWriter(static::editorBlockInstanceService(), static::requestDtoFactory()),
            static::editorCacheInvalidationClient(),
            config(\Config\Editor::class),
            static::resourceAuthorization(),
        );
    }

    public static function editorPreviewProjector(bool $getShared = true): \App\Interfaces\Editor\EditorPreviewProjectorInterface
    {
        if ($getShared) {
            return static::getSharedInstance('editorPreviewProjector');
        }

        return new \App\Libraries\Cms\EditorPreviewProjector(
            \Config\Database::connect(),
            static::fileUrlResolver(),
            new \App\Libraries\Cms\EditorFieldValidator(),
            new \App\Libraries\Cms\TranslationFallbackResolver(),
            config(\Config\Editor::class),
        );
    }

    public static function editorDocumentReader(bool $getShared = true): \App\Interfaces\Editor\EditorDocumentReaderInterface
    {
        if ($getShared) {
            return static::getSharedInstance('editorDocumentReader');
        }

        $db = \Config\Database::connect();

        return new \App\Services\Editor\EditorDocumentReader(
            $db,
            new \App\Libraries\Cms\EditorDocumentSnapshotReader($db),
            new \App\Libraries\Cms\EditorDocumentAssembler(static::fileUrlResolver()),
            static::resourceAuthorization(),
        );
    }

    public static function fileUrlResolver(bool $getShared = true): \App\Libraries\Cms\FileUrlResolver
    {
        if ($getShared) {
            return static::getSharedInstance('fileUrlResolver');
        }

        return new \App\Libraries\Cms\FileUrlResolver(static::hubClient());
    }

    public static function fileReferenceSynchronizer(bool $getShared = true): \App\Libraries\Cms\FileReferenceSynchronizer
    {
        if ($getShared) {
            return static::getSharedInstance('fileReferenceSynchronizer');
        }

        return new \App\Libraries\Cms\FileReferenceSynchronizer(static::fileUrlResolver());
    }

    public static function blockInstancePurger(bool $getShared = true): \App\Libraries\Cms\BlockInstancePurger
    {
        if ($getShared) {
            return static::getSharedInstance('blockInstancePurger');
        }

        return new \App\Libraries\Cms\BlockInstancePurger();
    }

    public static function fileUsageService(bool $getShared = true): \App\Services\Cms\FileUsageService
    {
        if ($getShared) {
            return static::getSharedInstance('fileUsageService');
        }

        return new \App\Services\Cms\FileUsageService(\Config\Database::connect());
    }

    public static function fileTranslationResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('fileTranslationResponseMapper');
        }

        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(
            \App\DTO\Response\Cms\FileTranslationResponseDTO::class
        );
    }

    public static function fileTranslationService(bool $getShared = true): \App\Interfaces\Cms\FileTranslationServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('fileTranslationService');
        }

        return new \App\Services\Cms\FileTranslationService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\FileTranslationModel::class)),
            static::fileTranslationResponseMapper()
        );
    }

    public static function blockInstanceSerializer(bool $getShared = true): \App\Libraries\Cms\BlockInstanceSerializer
    {
        if ($getShared) {
            return static::getSharedInstance('blockInstanceSerializer');
        }

        return new \App\Libraries\Cms\BlockInstanceSerializer(static::fileUrlResolver());
    }

    public static function publicEntryReader(bool $getShared = true): \App\Services\Cms\PublicEntryReader
    {
        if ($getShared) {
            return static::getSharedInstance('publicEntryReader');
        }

        return new \App\Services\Cms\PublicEntryReader(
            static::fileUrlResolver(),
            static::entryListingContentResolver(),
            static::blockInstanceSerializer(),
            static::entryTaxonomyPivotResolver()
        );
    }

    public static function entryTaxonomyPivotResolver(bool $getShared = true): \App\Libraries\Cms\EntryTaxonomyPivotResolver
    {
        if ($getShared) {
            return static::getSharedInstance('entryTaxonomyPivotResolver');
        }

        return new \App\Libraries\Cms\EntryTaxonomyPivotResolver(\Config\Database::connect());
    }

    public static function entryListingContentResolver(bool $getShared = true): \App\Libraries\Cms\EntryListingContentResolver
    {
        if ($getShared) {
            return static::getSharedInstance('entryListingContentResolver');
        }

        return new \App\Libraries\Cms\EntryListingContentResolver(static::blockInstanceSerializer());
    }
    public static function pageResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('pageResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Cms\PageResponseDTO::class);
    }
    public static function pageService(bool $getShared = true): \App\Interfaces\Cms\PageServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('pageService');
        }
        return new \App\Services\Cms\PageService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\PageModel::class)),
            static::pageResponseMapper(),
            static::slugRedirectRecorder(),
            static::cacheInvalidationClient(),
            static::fileUrlResolver(),
            static::fileReferenceSynchronizer(),
            static::publicPageReader(),
            static::blockInstancePurger(),
            static::requestDtoFactory(),
            static::translationSynchronizer(),
            static::pageListRepository(),
            static::resourceAuthorization()
        );
    }

    public static function pageQualityService(bool $getShared = true): \App\Interfaces\Cms\PageQualityServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('pageQualityService');
        }

        return new \App\Services\Cms\PageQualityService(
            model(\App\Models\PageModel::class),
            model(\App\Models\PageTranslationModel::class),
            model(\App\Models\LanguageModel::class),
            model(\App\Models\BlockInstanceModel::class),
        );
    }

    public static function publicPageReader(bool $getShared = true): \App\Services\Cms\PublicPageReader
    {
        if ($getShared) {
            return static::getSharedInstance('publicPageReader');
        }

        return new \App\Services\Cms\PublicPageReader(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\PageModel::class)),
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\LanguageModel::class)),
            static::slugRouter(),
            static::translationResolver(),
            static::blockInstanceSerializer()
        );
    }

    public static function publicBootstrapService(bool $getShared = true): \App\Services\Cms\PublicBootstrapService
    {
        if ($getShared) {
            return static::getSharedInstance('publicBootstrapService');
        }

        return new \App\Services\Cms\PublicBootstrapService(
            static::settingService(),
            static::menuService(),
            static::pageService(),
            static::collectionService(),
            static::entryService(),
            static::publicLocaleResolver(),
            static::requestDtoFactory(),
        );
    }

    public static function slugRouter(bool $getShared = true): \App\Libraries\Cms\SlugRouter
    {
        if ($getShared) {
            return static::getSharedInstance('slugRouter');
        }

        return new \App\Libraries\Cms\SlugRouter();
    }
    public static function menuResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('menuResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Cms\MenuResponseDTO::class);
    }
    public static function menuService(bool $getShared = true): \App\Interfaces\Cms\MenuServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('menuService');
        }
        return new \App\Services\Cms\MenuService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\MenuModel::class)),
            static::menuResponseMapper(),
            static::cacheInvalidationClient(),
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\MenuItemModel::class)),
            static::translationResolver(),
            static::menuItemService(),
            static::translationSynchronizer(),
            static::menuListRepository()
        );
    }
    public static function menuItemResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('menuItemResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Cms\MenuItemResponseDTO::class);
    }
    public static function menuItemService(bool $getShared = true): \App\Interfaces\Cms\MenuItemServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('menuItemService');
        }
        return new \App\Services\Cms\MenuItemService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\MenuItemModel::class)), static::menuItemResponseMapper(), static::cacheInvalidationClient(), static::translationResolver(), static::slugRouter(), static::translationSynchronizer());
    }
    public static function blockTypeResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('blockTypeResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Cms\BlockTypeResponseDTO::class);
    }
    public static function blockTypeService(bool $getShared = true): \App\Interfaces\Cms\BlockTypeServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('blockTypeService');
        }
        return new \App\Services\Cms\BlockTypeService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\BlockTypeModel::class)),
            static::blockTypeResponseMapper(),
            \Config\Database::connect(),
            static::fileReferenceSynchronizer(),
            static::ownerUsageResolver()
        );
    }
    public static function blockTemplateCatalog(bool $getShared = true): \App\Libraries\Cms\BlockTemplateCatalog
    {
        if ($getShared) {
            return static::getSharedInstance('blockTemplateCatalog');
        }
        return new \App\Libraries\Cms\BlockTemplateCatalog(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\BlockTypeModel::class)));
    }
    public static function blockInstanceResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('blockInstanceResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Cms\BlockInstanceResponseDTO::class);
    }
    public static function blockInstanceService(bool $getShared = true): \App\Interfaces\Cms\BlockInstanceServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('blockInstanceService');
        }
        return new \App\Services\Cms\BlockInstanceService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\BlockInstanceModel::class)),
            static::blockInstanceResponseMapper(),
            static::fileUrlResolver(),
            static::fileReferenceSynchronizer(),
            static::cacheInvalidationClient(),
            static::translationSynchronizer(),
            static::resourceAuthorization(),
        );
    }
    public static function collectionResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('collectionResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Cms\CollectionResponseDTO::class);
    }
    public static function collectionService(bool $getShared = true): \App\Interfaces\Cms\CollectionServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('collectionService');
        }
        return new \App\Services\Cms\CollectionService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\CollectionModel::class)),
            static::collectionResponseMapper(),
            static::cacheInvalidationClient(),
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\LanguageModel::class)),
            static::publicCollectionReader(),
            static::translationSynchronizer(),
            static::collectionPublicSlugProjection(),
            static::collectionListRepository(),
            static::resourceAuthorization()
        );
    }

    public static function publicSlugStore(bool $getShared = true): \dcardenasl\Ci4ApiCore\Localization\PublicSlugStore
    {
        if ($getShared) {
            return static::getSharedInstance('publicSlugStore');
        }

        return new \dcardenasl\Ci4ApiCore\Localization\PublicSlugStore(
            model(\App\Models\PublicSlugModel::class),
            new \dcardenasl\Ci4ApiCore\Localization\SlugGenerator(),
            new \dcardenasl\Ci4ApiCore\Localization\RequestLocaleResolver(service('request')),
            config('Localization')
        );
    }

    public static function collectionPublicSlugProjection(bool $getShared = true): \App\Libraries\Translation\CollectionPublicSlugProjection
    {
        if ($getShared) {
            return static::getSharedInstance('collectionPublicSlugProjection');
        }

        return new \App\Libraries\Translation\CollectionPublicSlugProjection(
            \Config\Database::connect(),
            static::publicSlugStore()
        );
    }

    public static function sortOrderService(bool $getShared = true): \App\Services\Cms\SortOrderService
    {
        if ($getShared) {
            return static::getSharedInstance('sortOrderService');
        }

        return new \App\Services\Cms\SortOrderService(
            \Config\Database::connect(),
            static::cacheInvalidationClient(),
            static::resourceAuthorization(),
        );
    }

    public static function publicCollectionReader(bool $getShared = true): \App\Services\Cms\PublicCollectionReader
    {
        if ($getShared) {
            return static::getSharedInstance('publicCollectionReader');
        }

        return new \App\Services\Cms\PublicCollectionReader(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\PageModel::class)),
            static::translationResolver(),
            static::slugRouter(),
            static::collectionPublicSlugProjection()
        );
    }
    public static function entryResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('entryResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Cms\EntryResponseDTO::class);
    }
    public static function entryBlockTemplateInitializer(bool $getShared = true): \App\Services\Cms\EntryBlockTemplateInitializer
    {
        if ($getShared) {
            return static::getSharedInstance('entryBlockTemplateInitializer');
        }

        return new \App\Services\Cms\EntryBlockTemplateInitializer();
    }
    public static function entryService(bool $getShared = true): \App\Interfaces\Cms\EntryServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('entryService');
        }
        return new \App\Services\Cms\EntryService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\EntryModel::class)),
            static::entryResponseMapper(),
            static::slugRedirectRecorder(),
            static::cacheInvalidationClient(),
            static::fileUrlResolver(),
            static::fileReferenceSynchronizer(),
            static::translationResolver(),
            static::publicEntryReader(),
            static::entryTaxonomyPivotResolver(),
            static::entryBlockTemplateInitializer(),
            static::blockInstancePurger(),
            static::entryCategoryLinkRepository(),
            static::entryTagLinkRepository(),
            static::translationSynchronizer(),
            static::entryListRepository(),
            static::resourceAuthorization()
        );
    }

    public static function entryCategoryLinkRepository(bool $getShared = true): \App\Interfaces\Cms\EntryTaxonomyLinkRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('entryCategoryLinkRepository');
        }

        return new \App\Repositories\Cms\EntryCategoryLinkRepository(
            model(\App\Models\EntryCategoryModel::class)
        );
    }

    public static function entryTagLinkRepository(bool $getShared = true): \App\Interfaces\Cms\EntryTaxonomyLinkRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('entryTagLinkRepository');
        }

        return new \App\Repositories\Cms\EntryTagLinkRepository(
            model(\App\Models\EntryTagModel::class)
        );
    }
    public static function categoryResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('categoryResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Cms\CategoryResponseDTO::class);
    }
    public static function categoryService(bool $getShared = true): \App\Interfaces\Cms\CategoryServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('categoryService');
        }
        return new \App\Services\Cms\CategoryService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\CategoryModel::class)),
            static::categoryResponseMapper(),
            static::translationResolver(),
            static::cacheInvalidationClient(),
            static::translationSynchronizer(),
            static::categoryListRepository()
        );
    }
    public static function tagResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('tagResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Cms\TagResponseDTO::class);
    }
    public static function tagService(bool $getShared = true): \App\Interfaces\Cms\TagServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('tagService');
        }
        return new \App\Services\Cms\TagService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\TagModel::class)), static::tagResponseMapper(), static::cacheInvalidationClient(), static::translationResolver(), static::translationSynchronizer(), static::tagListRepository());
    }
    public static function redirectResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('redirectResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Cms\RedirectResponseDTO::class);
    }
    public static function redirectService(bool $getShared = true): \App\Interfaces\Cms\RedirectServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('redirectService');
        }
        return new \App\Services\Cms\RedirectService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\RedirectModel::class)),
            static::redirectResponseMapper(),
            static::cacheInvalidationClient(),
            static::publicRedirectResolver()
        );
    }

    public static function publicRedirectResolver(bool $getShared = true): \App\Libraries\Cms\PublicRedirectResolver
    {
        if ($getShared) {
            return static::getSharedInstance('publicRedirectResolver');
        }

        return new \App\Libraries\Cms\PublicRedirectResolver(
            \Config\Database::connect(),
            static::translationResolver(),
            static::slugRouter()
        );
    }

    public static function slugRedirectRecorder(bool $getShared = true): \App\Libraries\Cms\SlugRedirectRecorder
    {
        if ($getShared) {
            return static::getSharedInstance('slugRedirectRecorder');
        }

        return new \App\Libraries\Cms\SlugRedirectRecorder();
    }

    public static function translationAuditService(bool $getShared = true): \App\Interfaces\Cms\TranslationAuditServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('translationAuditService');
        }

        $support = new \App\Libraries\Cms\TranslationAuditSupport();
        $repo = static fn (string $modelClass): \dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface
            => new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model($modelClass));

        return new \App\Services\Cms\TranslationAuditService(
            $repo(\App\Models\LanguageModel::class),
            $repo(\App\Models\PageModel::class),
            $repo(\App\Models\PageTranslationModel::class),
            $repo(\App\Models\MenuModel::class),
            $repo(\App\Models\MenuTranslationModel::class),
            $repo(\App\Models\MenuItemModel::class),
            $repo(\App\Models\MenuItemTranslationModel::class),
            $repo(\App\Models\SettingModel::class),
            $repo(\App\Models\SettingTranslationModel::class),
            $repo(\App\Models\CollectionModel::class),
            $repo(\App\Models\CollectionTranslationModel::class),
            $repo(\App\Models\CategoryModel::class),
            $repo(\App\Models\CategoryTranslationModel::class),
            $repo(\App\Models\TagModel::class),
            $repo(\App\Models\TagTranslationModel::class),
            $repo(\App\Models\EntryModel::class),
            $repo(\App\Models\EntryTranslationModel::class),
            $repo(\App\Models\FormModel::class),
            $repo(\App\Models\FormTranslationModel::class),
            $repo(\App\Models\FormFieldModel::class),
            $repo(\App\Models\FormFieldTranslationModel::class),
            $support,
            new \App\Services\Cms\BlockInstanceTranslationAuditor($support),
        );
    }

    public static function translationSynchronizer(bool $getShared = true): \App\Libraries\Cms\TranslationSynchronizer
    {
        if ($getShared) {
            return static::getSharedInstance('translationSynchronizer');
        }

        return new \App\Libraries\Cms\TranslationSynchronizer(\Config\Database::connect());
    }

    public static function cacheInvalidationClient(bool $getShared = true): \App\Libraries\Cms\CacheInvalidationClient
    {
        if ($getShared) {
            return static::getSharedInstance('cacheInvalidationClient');
        }

        return new \App\Libraries\Cms\CacheInvalidationClient(
            queueManager: static::cacheQueueManager(),
            queueName: config('Queue')->defaultQueue,
        );
    }

    public static function publicLocaleResolver(bool $getShared = true): \App\Libraries\Cms\PublicLocaleResolver
    {
        if ($getShared) {
            return static::getSharedInstance('publicLocaleResolver');
        }

        return new \App\Libraries\Cms\PublicLocaleResolver(\Config\Database::connect());
    }

    public static function ownerUsageResolver(bool $getShared = true): \App\Libraries\Cms\OwnerUsageResolver
    {
        if ($getShared) {
            return static::getSharedInstance('ownerUsageResolver');
        }

        return new \App\Libraries\Cms\OwnerUsageResolver(\Config\Database::connect());
    }

    public static function fieldPrimitiveRegistry(bool $getShared = true): \App\Libraries\Cms\FieldPrimitiveRegistry
    {
        if ($getShared) {
            return static::getSharedInstance('fieldPrimitiveRegistry');
        }

        return new \App\Libraries\Cms\FieldPrimitiveRegistry();
    }

    public static function blockSchemaIntrospector(bool $getShared = true): \App\Libraries\Cms\BlockSchemaIntrospector
    {
        if ($getShared) {
            return static::getSharedInstance('blockSchemaIntrospector');
        }

        return new \App\Libraries\Cms\BlockSchemaIntrospector(static::fieldPrimitiveRegistry());
    }

    public static function wizardConfigService(bool $getShared = true): \App\Interfaces\Cms\WizardConfigServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('wizardConfigService');
        }

        return new \App\Services\Cms\WizardConfigService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\LanguageModel::class)),
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\CollectionModel::class)),
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\PageModel::class)),
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\PageTranslationModel::class)),
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\MenuModel::class)),
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\BlockTypeModel::class)),
            static::ownerUsageResolver(),
            static::blockSchemaIntrospector(),
            static::fieldPrimitiveRegistry()
        );
    }

    public static function formService(bool $getShared = true): \App\Services\Cms\FormService
    {
        if ($getShared) {
            return static::getSharedInstance('formService');
        }

        return new \App\Services\Cms\FormService(
            model(\App\Models\FormModel::class),
            model(\App\Models\FormTranslationModel::class),
            static::cacheInvalidationClient(),
            \Config\Database::connect(),
            static::ownerUsageResolver(),
            static::formFieldService(),
            static::translationSynchronizer(),
        );
    }

    public static function formFieldService(bool $getShared = true): \App\Services\Cms\FormFieldService
    {
        if ($getShared) {
            return static::getSharedInstance('formFieldService');
        }

        return new \App\Services\Cms\FormFieldService(
            model(\App\Models\FormFieldModel::class),
            model(\App\Models\FormFieldTranslationModel::class),
            static::cacheInvalidationClient(),
            \Config\Database::connect(),
            static::translationSynchronizer(),
        );
    }

    public static function formPublicDefinitionAssembler(bool $getShared = true): \App\Services\Cms\FormPublicDefinitionAssembler
    {
        if ($getShared) {
            return static::getSharedInstance('formPublicDefinitionAssembler');
        }

        return new \App\Services\Cms\FormPublicDefinitionAssembler(
            model(\App\Models\FormModel::class),
            model(\App\Models\FormTranslationModel::class),
            model(\App\Models\FormFieldModel::class),
            model(\App\Models\FormFieldTranslationModel::class),
        );
    }

    public static function formSubmissionService(bool $getShared = true): \App\Services\Cms\FormSubmissionService
    {
        if ($getShared) {
            return static::getSharedInstance('formSubmissionService');
        }

        return new \App\Services\Cms\FormSubmissionService(
            model(\App\Models\FormSubmissionModel::class),
            static::queueManager()
        );
    }

    public static function analyticsService(bool $getShared = true): \App\Services\Cms\AnalyticsService
    {
        if ($getShared) {
            return static::getSharedInstance('analyticsService');
        }

        return new \App\Services\Cms\AnalyticsService(
            new \App\Models\PageViewModel()
        );
    }
}
