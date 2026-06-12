<?php

declare(strict_types=1);

namespace Config;

trait CmsDomainServices
{
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
            static::settingResponseMapper()
        );
    }

    public static function translationResolver(bool $getShared = true): \App\Libraries\Cms\TranslationResolver
    {
        if ($getShared) {
            return static::getSharedInstance('translationResolver');
        }

        return new \App\Libraries\Cms\TranslationResolver();
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

        return new \App\Libraries\Cms\BlockInstanceSerializer();
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
        return new \App\Services\Cms\PageService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\PageModel::class)), static::pageResponseMapper());
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
        return new \App\Services\Cms\MenuService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\MenuModel::class)), static::menuResponseMapper());
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
        return new \App\Services\Cms\MenuItemService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\MenuItemModel::class)), static::menuItemResponseMapper());
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
        return new \App\Services\Cms\BlockTypeService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\BlockTypeModel::class)), static::blockTypeResponseMapper());
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
        return new \App\Services\Cms\BlockInstanceService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\BlockInstanceModel::class)), static::blockInstanceResponseMapper());
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
        return new \App\Services\Cms\CollectionService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\CollectionModel::class)), static::collectionResponseMapper());
    }
    public static function entryResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('entryResponseMapper');
        }
        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(\App\DTO\Response\Cms\EntryResponseDTO::class);
    }
    public static function entryService(bool $getShared = true): \App\Interfaces\Cms\EntryServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('entryService');
        }
        return new \App\Services\Cms\EntryService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\EntryModel::class)), static::entryResponseMapper());
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
        return new \App\Services\Cms\CategoryService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\CategoryModel::class)), static::categoryResponseMapper());
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
        return new \App\Services\Cms\TagService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\TagModel::class)), static::tagResponseMapper());
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
        return new \App\Services\Cms\RedirectService(new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\RedirectModel::class)), static::redirectResponseMapper());
    }

    public static function slugRedirectRecorder(bool $getShared = true): \App\Libraries\Cms\SlugRedirectRecorder
    {
        if ($getShared) {
            return static::getSharedInstance('slugRedirectRecorder');
        }

        return new \App\Libraries\Cms\SlugRedirectRecorder();
    }
}
