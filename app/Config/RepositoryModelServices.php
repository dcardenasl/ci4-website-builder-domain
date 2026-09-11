<?php

declare(strict_types=1);

namespace Config;

trait RepositoryModelServices
{
    public static function entryListRepository(bool $getShared = true): \App\Interfaces\Cms\EntryListRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('entryListRepository');
        }

        return new \App\Repositories\Cms\EntryListRepository(
            model(\App\Models\EntryModel::class),
            \Config\Database::connect(),
        );
    }

    public static function categoryListRepository(bool $getShared = true): \App\Interfaces\Cms\AdminListProjectionRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('categoryListRepository');
        }

        return new \App\Repositories\Cms\CategoryListRepository(
            model(\App\Models\CategoryModel::class),
            \Config\Database::connect(),
        );
    }

    public static function collectionListRepository(bool $getShared = true): \App\Interfaces\Cms\AdminListProjectionRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('collectionListRepository');
        }

        return new \App\Repositories\Cms\CollectionListRepository(
            model(\App\Models\CollectionModel::class),
            \Config\Database::connect(),
        );
    }

    public static function pageListRepository(bool $getShared = true): \App\Interfaces\Cms\AdminListProjectionRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('pageListRepository');
        }

        return new \App\Repositories\Cms\PageListRepository(
            model(\App\Models\PageModel::class),
            \Config\Database::connect(),
        );
    }

    public static function tagListRepository(bool $getShared = true): \App\Interfaces\Cms\AdminListProjectionRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('tagListRepository');
        }

        return new \App\Repositories\Cms\TagListRepository(
            model(\App\Models\TagModel::class),
            \Config\Database::connect(),
        );
    }

    public static function settingListRepository(bool $getShared = true): \App\Interfaces\Cms\AdminListProjectionRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('settingListRepository');
        }

        return new \App\Repositories\Cms\SettingListRepository(
            model(\App\Models\SettingModel::class),
            \Config\Database::connect(),
        );
    }

    public static function menuListRepository(bool $getShared = true): \App\Interfaces\Cms\AdminListProjectionRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('menuListRepository');
        }

        return new \App\Repositories\Cms\MenuListRepository(
            model(\App\Models\MenuModel::class),
            \Config\Database::connect(),
        );
    }

    public static function auditRepository(bool $getShared = true): \dcardenasl\Ci4ApiCore\Repositories\AuditRepositoryInterface
    {
        if ($getShared) {
            return static::getSharedInstance('auditRepository');
        }

        return new \App\Repositories\System\AuditRepository(model(\App\Models\AuditLogModel::class));
    }
}
