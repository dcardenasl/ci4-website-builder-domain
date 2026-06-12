<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class SlugRouter
{
    /** @var BaseConnection<mixed, mixed> */
    private BaseConnection $db;

    /**
     * @param BaseConnection<mixed, mixed>|null $db
     */
    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Resolve a slug path to a content ID.
     *
     * @param string $langCode Language code (e.g., 'es')
     * @param string $type Resource type (e.g., 'page')
     * @param string $slugPath Full slug path (e.g., 'nosotros/vision')
     * @return int|null Resolved ID or null if not found
     */
    public function resolve(string $langCode, string $type, string $slugPath): ?int
    {
        if ($type !== 'page') {
            return null;
        }

        // Get language ID
        $lang = $this->getLanguageByCode($langCode);
        if (!$lang) {
            return null;
        }
        $langId = (int) $lang['id'];

        // Get default language ID as fallback
        $defaultLang = $this->getDefaultLanguage();
        $defaultLangId = $defaultLang ? (int) $defaultLang['id'] : null;

        // Clean slug path
        $slugPath = trim($slugPath, '/');
        if ($slugPath === '') {
            return null;
        }

        $segments = explode('/', $slugPath);
        $currentParentId = null;

        foreach ($segments as $segment) {
            $pageId = $this->findPageBySlugAndParent($segment, $currentParentId, $langId);

            // If not found in target language, check in default language as fallback
            if ($pageId === null && $defaultLangId !== null && $langId !== $defaultLangId) {
                $pageId = $this->findPageBySlugAndParent($segment, $currentParentId, $defaultLangId);
            }

            if ($pageId === null) {
                return null;
            }

            $currentParentId = $pageId;
        }

        return $currentParentId;
    }

    /**
     * Find a page ID by its slug and parent_id for a given language.
     */
    private function findPageBySlugAndParent(string $slug, ?int $parentId, int $langId): ?int
    {
        $builder = $this->db->table('cms_pages p')
            ->select('p.id')
            ->join('cms_page_translations pt', 'p.id = pt.page_id')
            ->where('pt.slug', $slug)
            ->where('pt.language_id', $langId)
            ->where('p.status', 'published')
            ->where('p.deleted_at IS NULL');

        if ($parentId === null) {
            $builder->where('p.parent_id IS NULL');
        } else {
            $builder->where('p.parent_id', $parentId);
        }

        $query = $builder->get();
        if ($query === false) {
            return null;
        }
        $result = $query->getRow();

        return $result ? (int) $result->id : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getLanguageByCode(string $code): ?array
    {
        $result = $this->db->table('cms_languages')
            ->where('code', $code)
            ->get();

        return $result instanceof \CodeIgniter\Database\ResultInterface ? $result->getRowArray() : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getDefaultLanguage(): ?array
    {
        $result = $this->db->table('cms_languages')
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->get();

        return $result instanceof \CodeIgniter\Database\ResultInterface ? $result->getRowArray() : null;
    }
}
