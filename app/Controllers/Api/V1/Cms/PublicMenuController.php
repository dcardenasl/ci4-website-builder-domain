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
                // Get request language from header or fallback
                $lang = $this->request->getLocale();

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

                // Resolve translations for each menu item
                $flatList = [];
                $slugRouter = Services::slugRouter();
                foreach ($items as $item) {
                    if ($item instanceof \App\Entities\MenuItemEntity) {
                        $resolved = $translationResolver->resolve('menu_item', (int) $item->id, $lang);

                        $customUrl = $resolved['custom_url'] ?? null;
                        if (($item->link_type ?? '') === 'page' && $item->page_id !== null) {
                            $pageSlug = $slugRouter->resolveSlug($lang, 'page', (int) $item->page_id);
                            if ($pageSlug !== null) {
                                $customUrl = '/' . ltrim($pageSlug, '/');
                            }
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
}
