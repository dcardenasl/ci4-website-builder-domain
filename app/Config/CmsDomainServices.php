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
}
