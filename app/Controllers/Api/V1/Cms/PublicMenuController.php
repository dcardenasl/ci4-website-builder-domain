<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicMenuController extends ApiController
{
    protected function resolveDefaultService(): object
    {
        return Services::menuService();
    }

    /**
     * Resolve a public menu tree by its menu_key and language.
     *
     * @param string $menuKey Unique menu identifier
     */
    public function show(string $menuKey): ResponseInterface
    {
        return $this->handleRequest(
            function () use ($menuKey): ResponseInterface {
                // The API's static framework locale list must not decide CMS
                // content language. The public web client sends the locale in
                // Accept-Language after discovering it from the CMS.
                $lang = $this->publicLocale();

                // Find menu
                $menuModel = model(\App\Models\MenuModel::class);
                $menu = $menuModel->where('menu_key', $menuKey)
                    ->where('is_active', 1)
                    ->first();

                if (!$menu) {
                    throw new NotFoundException(lang('Menus.not_found'));
                }

                // Fetch menu items
                $menuItemModel = model(\App\Models\MenuItemModel::class);
                $items = $menuItemModel->where('menu_id', $menu->id)
                    ->where('is_active', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();

                $translationResolver = Services::translationResolver();
                $menuItemService = Services::menuItemService();

                // Resolve translations for each menu item
                $flatList = [];
                foreach ($items as $item) {
                    if ($item instanceof \App\Entities\MenuItemEntity) {
                        $resolved = $translationResolver->resolve('menu_item', (int) $item->id, $lang);

                        // Use MenuItemService to resolve the link
                        $customUrl = $menuItemService->resolveLink($item, $lang);

                        // Fall back to custom_url translation if no link was resolved
                        if ($customUrl === null && $item->link_type === 'custom_url') {
                            $customUrl = $resolved['custom_url'] ?? null;
                        }

                        $itemData = array_merge($item->toArray(), [
                            'label'       => $resolved['label'] ?? '',
                            'custom_url'  => $customUrl,
                            'is_fallback' => $resolved['is_fallback'] ?? false,
                        ]);

                        $flatList[] = $itemData;
                    }
                }

                // Reconstruct tree
                $tree = $this->buildTree($flatList, null);

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => [
                        'menu_key' => $menu->menu_key,
                        'location' => $menu->location,
                        'items'    => $tree,
                    ],
                ])->setStatusCode(200);
            }
        );
    }

    /**
     * Helper to reconstruct hierarchical tree from flat list of menu items.
     *
     * @param array<array<string, mixed>> $items
     * @param int|null $parentId
     * @return array<array<string, mixed>>
     */
    private function buildTree(array &$items, ?int $parentId): array
    {
        $branch = [];

        foreach ($items as $item) {
            $itemParentId = $item['parent_id'] !== null ? (int) $item['parent_id'] : null;

            if ($itemParentId === $parentId) {
                $children = $this->buildTree($items, (int) $item['id']);
                $item['children'] = $children;
                $branch[] = $item;
            }
        }

        return $branch;
    }

    private function publicLocale(): string
    {
        $header = trim($this->request->getHeaderLine('Accept-Language'));
        $locale = strtolower(trim((string) explode(',', $header)[0]));
        $locale = preg_replace('/[^a-z0-9-].*$/', '', $locale) ?? '';

        if ($locale !== '') {
            $active = model(\App\Models\LanguageModel::class)
                ->where('is_active', 1)
                ->where('code', $locale)
                ->first();
            if ($active !== null) {
                return $locale;
            }
        }

        $default = model(\App\Models\LanguageModel::class)
            ->where('is_active', 1)
            ->where('is_default', 1)
            ->first();

        return $default !== null ? (string) $default->code : 'es';
    }
}
