<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Models\BlockTypeModel;
use App\Models\CollectionModel;
use App\Models\CollectionTranslationModel;
use App\Models\LanguageModel;
use App\Models\MenuModel;
use App\Models\MenuTranslationModel;
use App\Models\PageModel;
use App\Models\PageTranslationModel;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class WizardConfigController extends ApiController
{
    protected function resolveDefaultService(): object
    {
        return new \stdClass();
    }

    public function config(): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.entries.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }

                // ── Languages ──────────────────────────────────────────────────
                /** @var LanguageModel $langModel */
                $langModel = model(LanguageModel::class);
                /** @var array<int, array<string, mixed>> $languages */
                $languages = $langModel
                    ->select('id, code, name, native_name, is_default')
                    ->where('is_active', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->asArray()
                    ->findAll();

                $languagesData = array_map(static fn (array $l): array => [
                    'id'         => (int) ($l['id'] ?? 0),
                    'code'       => (string) ($l['code'] ?? ''),
                    'name'       => (string) ($l['name'] ?? ''),
                    'native_name' => (string) ($l['native_name'] ?? ''),
                    'is_default' => (bool) ($l['is_default'] ?? false),
                ], $languages);

                $defaultLangId = $this->resolveDefaultLanguageId($languagesData);

                // ── Collections ───────────────────────────────────────────────
                /** @var CollectionModel $collectionModel */
                $collectionModel = model(CollectionModel::class);

                /** @var array<int, array<string, mixed>> $collections */
                $collections = $collectionModel
                    ->where('is_active', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->asArray()
                    ->findAll();

                /** @var list<int|string> $collectionIds */
                $collectionIds = array_column($collections, 'id');

                /** @var array<int|string, string> $translationsByCollection */
                $translationsByCollection = [];
                if (!empty($collectionIds)) {
                    /** @var CollectionTranslationModel $ctModel */
                    $ctModel = model(CollectionTranslationModel::class);
                    /** @var array<int, array<string, mixed>> $ctRows */
                    $ctRows = $ctModel->whereIn('collection_id', $collectionIds)->asArray()->findAll();
                    foreach ($ctRows as $ct) {
                        $cid = $ct['collection_id'] ?? null;
                        if ($cid !== null && !isset($translationsByCollection[$cid])) {
                            $translationsByCollection[$cid] = (string) ($ct['name'] ?? $cid);
                        }
                    }
                }

                $collectionsData = array_map(function (array $c) use ($translationsByCollection): array {
                    $raw = $c['wizard_config'] ?? null;
                    $wizardConfig = null;
                    if (is_string($raw) && $raw !== '') {
                        $decoded = json_decode($raw, true);
                        $wizardConfig = is_array($decoded) ? $decoded : null;
                    } elseif (is_array($raw)) {
                        $wizardConfig = $raw;
                    }

                    $cid  = $c['id'] ?? null;
                    $name = ($cid !== null && isset($translationsByCollection[$cid]))
                        ? $translationsByCollection[$cid]
                        : (string) ($c['collection_key'] ?? '');

                    $icon        = is_array($wizardConfig) ? (string) ($wizardConfig['icon'] ?? '📄') : '📄';
                    $description = is_array($wizardConfig) ? (string) ($wizardConfig['description'] ?? '') : '';

                    return [
                        'id'             => $cid,
                        'collection_key' => $c['collection_key'] ?? '',
                        'name'           => $name,
                        'icon'           => $icon,
                        'description'    => $description,
                        'wizard_config'  => $wizardConfig,
                    ];
                }, $collections);

                // ── Pages ─────────────────────────────────────────────────────
                /** @var PageModel $pageModel */
                $pageModel = model(PageModel::class);
                /** @var array<int, array<string, mixed>> $pages */
                $pages = $pageModel
                    ->select('id, sort_order')
                    ->where('status !=', 'archived')
                    ->orderBy('sort_order', 'ASC')
                    ->limit(50)
                    ->asArray()
                    ->findAll();

                /** @var list<int|string> $pageIds */
                $pageIds = array_column($pages, 'id');

                /** @var array<int|string, array{title: string, slug: string}> $titlesByPage */
                $titlesByPage = [];
                if (!empty($pageIds)) {
                    /** @var PageTranslationModel $ptModel */
                    $ptModel = model(PageTranslationModel::class);
                    /** @var array<int, array<string, mixed>> $ptRows */
                    $ptRows = $ptModel
                        ->select('page_id, title, slug')
                        ->whereIn('page_id', $pageIds)
                        ->where('language_id', $defaultLangId)
                        ->asArray()
                        ->findAll();
                    foreach ($ptRows as $pt) {
                        $pid = $pt['page_id'] ?? null;
                        if ($pid !== null && !isset($titlesByPage[$pid])) {
                            $titlesByPage[$pid] = [
                                'title' => (string) ($pt['title'] ?? ''),
                                'slug'  => (string) ($pt['slug'] ?? ''),
                            ];
                        }
                    }
                }

                $pagesData = array_map(function (array $p) use ($titlesByPage): array {
                    $pid  = $p['id'] ?? null;
                    $info = ($pid !== null && isset($titlesByPage[$pid]))
                        ? $titlesByPage[$pid]
                        : ['title' => 'Page #' . $pid, 'slug' => ''];

                    return ['id' => $pid, 'title' => $info['title'], 'slug' => $info['slug']];
                }, $pages);

                // ── Menus ─────────────────────────────────────────────────────
                /** @var MenuModel $menuModel */
                $menuModel = model(MenuModel::class);
                /** @var array<int, array<string, mixed>> $menus */
                $menus = $menuModel->where('is_active', 1)->asArray()->findAll();

                /** @var list<int|string> $menuIds */
                $menuIds = array_column($menus, 'id');

                /** @var array<int|string, string> $namesByMenu */
                $namesByMenu = [];
                if (!empty($menuIds)) {
                    /** @var MenuTranslationModel $mtModel */
                    $mtModel = model(MenuTranslationModel::class);
                    /** @var array<int, array<string, mixed>> $mtRows */
                    $mtRows = $mtModel
                        ->whereIn('menu_id', $menuIds)
                        ->where('language_id', $defaultLangId)
                        ->asArray()
                        ->findAll();
                    foreach ($mtRows as $mt) {
                        $mid = $mt['menu_id'] ?? null;
                        if ($mid !== null && !isset($namesByMenu[$mid])) {
                            $namesByMenu[$mid] = (string) ($mt['name'] ?? '');
                        }
                    }
                }

                $menusData = array_map(function (array $m) use ($namesByMenu): array {
                    $mid  = $m['id'] ?? null;
                    $mkey = (string) ($m['menu_key'] ?? '');
                    return [
                        'id'       => $mid,
                        'menu_key' => $mkey,
                        'name'     => ($mid !== null && isset($namesByMenu[$mid])) ? $namesByMenu[$mid] : $mkey,
                    ];
                }, $menus);

                // ── Block type schemas ─────────────────────────────────────────
                // Keyed by block_key so the wizard can look up field labels and
                // types when rendering the block editor (screen B3).
                /** @var BlockTypeModel $btModel */
                $btModel = model(BlockTypeModel::class);
                /** @var array<int, array<string, mixed>> $blockTypes */
                $blockTypes = $btModel
                    ->select('id, block_key, name, description, icon, schema_definition, supports_pages, supports_entries, is_container, is_active, sort_order')
                    ->where('is_active', 1)
                    ->asArray()
                    ->findAll();

                /** @var array<string, array<string, mixed>> $blockTypesMap */
                $blockTypesMap = [];
                foreach ($blockTypes as $bt) {
                    $bkey = (string) ($bt['block_key'] ?? '');
                    if ($bkey === '') {
                        continue;
                    }
                    $raw    = $bt['schema_definition'] ?? null;
                    $schema = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
                    $blockTypesMap[$bkey] = [
                        'id'            => (int) ($bt['id'] ?? 0),
                        'name'          => (string) ($bt['name'] ?? $bkey),
                        'description'   => $bt['description'] ?? null,
                        'icon'          => $bt['icon'] ?? null,
                        'fields'        => (array) (is_array($schema) ? ($schema['fields'] ?? []) : []),
                        'config_fields' => (array) (is_array($schema) ? ($schema['config_fields'] ?? []) : []),
                        'supports_pages'   => (bool) ($bt['supports_pages'] ?? false),
                        'supports_entries' => (bool) ($bt['supports_entries'] ?? false),
                        'is_container'     => (bool) ($bt['is_container'] ?? false),
                        'is_active'        => (bool) ($bt['is_active'] ?? false),
                        'sort_order'       => (int) ($bt['sort_order'] ?? 0),
                    ];
                }

                return [
                    'languages'      => $languagesData,
                    'default_language_id' => $defaultLangId,
                    'collections'    => $collectionsData,
                    'pages'          => $pagesData,
                    'menus'          => $menusData,
                    'block_types'    => $blockTypesMap,
                ];
            }
        );
    }

    /**
     * @param array<int, array<string, mixed>> $languages
     */
    private function resolveDefaultLanguageId(array $languages): int
    {
        foreach ($languages as $language) {
            if (! empty($language['is_default']) && isset($language['id'])) {
                return (int) $language['id'];
            }
        }

        foreach ($languages as $language) {
            if (isset($language['id'])) {
                return (int) $language['id'];
            }
        }

        return 1;
    }
}
