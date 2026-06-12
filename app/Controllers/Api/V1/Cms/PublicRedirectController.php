<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicRedirectController extends ApiController
{
    protected function resolveDefaultService(): object
    {
        return Services::redirectService();
    }

    /**
     * Resolve a redirect path.
     */
    public function resolve(string ...$segments): ResponseInterface
    {
        return $this->handleRequest(
            function () use ($segments): ResponseInterface {
                $path = implode('/', $segments);
                $cleanPath = rawurldecode($path);
                $cleanPath = trim($cleanPath, '/');

                // 1. Look in manual redirects (cms_redirects)
                $db = \Config\Database::connect();
                $manual = $db->table('cms_redirects')
                    ->where('old_path', $cleanPath)
                    ->where('is_active', 1)
                    ->get();

                $manualRow = null;
                if ($manual instanceof ResultInterface) {
                    $manualRow = $manual->getRowArray();
                }

                if ($manualRow) {
                    // Increment hit count
                    $db->table('cms_redirects')
                        ->where('id', (int)$manualRow['id'])
                        ->update([
                            'hit_count' => (int)$manualRow['hit_count'] + 1,
                            'last_hit_at' => date('Y-m-d H:i:s')
                        ]);

                    return $this->response->setJSON([
                        'status' => 'success',
                        'data'   => [
                            'new_url' => $manualRow['new_url'],
                            'redirect_type' => (int)$manualRow['redirect_type'],
                        ],
                    ])->setStatusCode(200);
                }

                // 2. Look in slug history redirects (cms_slug_redirects)
                $slugHistory = $db->table('cms_slug_redirects')
                    ->where('old_full_path', $cleanPath)
                    ->get();

                $historyRow = null;
                if ($slugHistory instanceof ResultInterface) {
                    $historyRow = $slugHistory->getRowArray();
                }

                if ($historyRow) {
                    $entityType = $historyRow['entity_type'];
                    $entityId = (int)$historyRow['entity_id'];
                    $langId = (int)$historyRow['language_id'];

                    // Get language code
                    $langResult = $db->table('cms_languages')->where('id', $langId)->get();
                    $langRow = null;
                    if ($langResult instanceof ResultInterface) {
                        $langRow = $langResult->getRowArray();
                    }
                    $langCode = $langRow ? $langRow['code'] : 'en';

                    // Resolve the new target URL
                    if ($entityType === 'page') {
                        // Find current slug of page
                        $pageTransResult = $db->table('cms_page_translations')
                            ->where('page_id', $entityId)
                            ->where('language_id', $langId)
                            ->get();

                        $pageTrans = null;
                        if ($pageTransResult instanceof ResultInterface) {
                            $pageTrans = $pageTransResult->getRowArray();
                        }

                        if ($pageTrans) {
                            $currentSlugPath = $this->buildPageSlugPath($entityId, $langId, $db);

                            return $this->response->setJSON([
                                'status' => 'success',
                                'data'   => [
                                    'new_url' => '/' . $langCode . '/pages/' . $currentSlugPath,
                                    'redirect_type' => 301,
                                ],
                            ])->setStatusCode(200);
                        }
                    } elseif ($entityType === 'entry') {
                        $entryTransResult = $db->table('cms_entry_translations')
                            ->where('entry_id', $entityId)
                            ->where('language_id', $langId)
                            ->get();

                        $entryTrans = null;
                        if ($entryTransResult instanceof ResultInterface) {
                            $entryTrans = $entryTransResult->getRowArray();
                        }

                        if ($entryTrans) {
                            $entryResult = $db->table('cms_entries')->where('id', $entityId)->get();
                            $entryRow = null;
                            if ($entryResult instanceof ResultInterface) {
                                $entryRow = $entryResult->getRowArray();
                            }

                            $prefix = '';
                            if ($entryRow) {
                                $collectionResult = $db->table('cms_collections')->where('id', (int)$entryRow['collection_id'])->get();
                                $collectionRow = null;
                                if ($collectionResult instanceof ResultInterface) {
                                    $collectionRow = $collectionResult->getRowArray();
                                }
                                if ($collectionRow) {
                                    $prefix = trim($collectionRow['url_prefix'], '/') . '/';
                                }
                            }

                            return $this->response->setJSON([
                                'status' => 'success',
                                'data'   => [
                                    'new_url' => '/' . $langCode . '/entries/' . $prefix . $entryTrans['slug'],
                                    'redirect_type' => 301,
                                ],
                            ])->setStatusCode(200);
                        }
                    }
                }

                throw new NotFoundException(lang('Redirects.not_found'));
            }
        );
    }

    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    private function buildPageSlugPath(int $pageId, int $languageId, BaseConnection $db): string
    {
        $pathSegments = [];
        $currentPageId = $pageId;

        while ($currentPageId !== null) {
            $pageResult = $db->table('cms_pages')->where('id', $currentPageId)->get();
            $pageRow = null;
            if ($pageResult instanceof ResultInterface) {
                $pageRow = $pageResult->getRowArray();
            }
            if (!$pageRow) {
                break;
            }

            $transResult = $db->table('cms_page_translations')
                ->where('page_id', $currentPageId)
                ->where('language_id', $languageId)
                ->get();

            $transRow = null;
            if ($transResult instanceof ResultInterface) {
                $transRow = $transResult->getRowArray();
            }

            if ($transRow) {
                $pathSegments[] = $transRow['slug'];
            }

            $currentPageId = $pageRow['parent_id'] !== null ? (int)$pageRow['parent_id'] : null;
        }

        return implode('/', array_reverse($pathSegments));
    }
}
