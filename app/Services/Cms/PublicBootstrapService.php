<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\PublicEntryShowRequestDTO;
use App\Interfaces\Cms\CollectionServiceInterface;
use App\Interfaces\Cms\EntryServiceInterface;
use App\Interfaces\Cms\MenuServiceInterface;
use App\Interfaces\Cms\PageServiceInterface;
use App\Interfaces\Cms\SettingServiceInterface;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Http\ApiResponse;
use dcardenasl\Ci4ApiCore\Support\RequestDtoFactory;

/**
 * Composes the public data needed to render a page in one Domain request.
 *
 * The individual public endpoints remain available for callers that need only
 * one resource. This service is the generic cold-page seam used by the Web
 * app to avoid repeating layout, collection and route discovery requests.
 */
final class PublicBootstrapService
{
    /** @param list<string> $menuKeys */
    public function __construct(
        private readonly SettingServiceInterface $settingService,
        private readonly MenuServiceInterface $menuService,
        private readonly PageServiceInterface $pageService,
        private readonly CollectionServiceInterface $collectionService,
        private readonly EntryServiceInterface $entryService,
        private readonly \App\Libraries\Cms\PublicLocaleResolver $localeResolver,
        private readonly RequestDtoFactory $requestDtoFactory,
        private readonly array $menuKeys = ['main', 'footer', 'legal'],
    ) {
    }

    /**
     * @return array{lang: string, settings: array<string, mixed>, menus: array<string, array<string, mixed>>}
     */
    public function layout(?string $acceptLanguageHeader): array
    {
        $lang = $this->localeResolver->resolve($acceptLanguageHeader);
        $menus = [];

        foreach ($this->menuKeys as $menuKey) {
            try {
                $menu = $this->menuService->showPublic($menuKey, $lang);
            } catch (\Throwable) {
                $menu = ['items' => []];
            }

            $menus[$menuKey] = $menu;
        }

        try {
            $settings = $this->settingService->listPublic($acceptLanguageHeader);
        } catch (\Throwable) {
            $settings = [];
        }

        return [
            'lang'     => $lang,
            'settings' => $settings,
            'menus'    => $menus,
        ];
    }

    /**
     * @return array{layout: array<string, mixed>, route: array{type: string, data?: array<string, mixed>, collection?: array<string, mixed>}}
     */
    public function pageBootstrap(
        string $path,
        ?string $acceptLanguageHeader,
        bool $preview = false,
    ): array {
        $layout = $this->layout($acceptLanguageHeader);
        $lang = $layout['lang'];
        $normalizedPath = trim($path, '/');

        try {
            $page = $this->pageService->showPublic($lang, $normalizedPath, $preview);

            return [
                'layout' => $layout,
                'route'  => [
                    'type' => 'page',
                    'data' => $this->toArray($page),
                ],
            ];
        } catch (NotFoundException) {
            // A missing page is expected while trying collection entries.
        }

        try {
            $collections = $this->collectionService->listPublic($lang);
        } catch (\Throwable) {
            $collections = [];
        }

        foreach ($collections as $collection) {
            if (! is_array($collection)) {
                continue;
            }

            $prefix = $this->collectionPrefix($collection, $lang);
            if ($prefix === '' || $normalizedPath === $prefix || ! str_starts_with($normalizedPath, $prefix . '/')) {
                continue;
            }

            $entrySlug = substr($normalizedPath, strlen($prefix) + 1);
            if ($entrySlug === '') {
                continue;
            }

            /** @var PublicEntryShowRequestDTO $request */
            $request = $this->requestDtoFactory->make(PublicEntryShowRequestDTO::class, [
                'lang'           => $lang,
                'collection_key' => (string) ($collection['collection_key'] ?? ''),
                'slug'           => $entrySlug,
                'preview'        => $preview ? '1' : '0',
            ]);

            try {
                $entry = $this->entryService->showPublic($request);

                return [
                    'layout' => $layout,
                    'route'  => [
                        'type'       => 'entry',
                        'collection' => $collection,
                        'data'       => $this->toArray($entry),
                    ],
                ];
            } catch (NotFoundException) {
                continue;
            }
        }

        return [
            'layout' => $layout,
            'route'  => ['type' => 'not_found'],
        ];
    }

    /** @param array<string, mixed> $collection */
    private function collectionPrefix(array $collection, string $lang): string
    {
        $indexPage = $collection['index_page'] ?? null;
        if (is_array($indexPage) && is_array($indexPage['localized_slugs'] ?? null)) {
            $slug = trim((string) ($indexPage['localized_slugs'][$lang] ?? ''), '/');
            if ($slug !== '') {
                return $slug;
            }
        }

        return trim((string) ($collection['collection_key'] ?? ''), '/');
    }

    /** @return array<string, mixed> */
    private function toArray(mixed $value): array
    {
        $converted = ApiResponse::convertDataToArrays($value);

        return is_array($converted) ? $converted : (array) $converted;
    }
}
