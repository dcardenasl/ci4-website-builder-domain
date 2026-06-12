<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class TranslationResolver
{
    /** @var BaseConnection<mixed, mixed> */
    private BaseConnection $db;

    private const RESOURCE_MAP = [
        'setting' => [
            'table'  => 'cms_setting_translations',
            'fk'     => 'setting_id',
            'fields' => ['setting_value'],
        ],
        'page' => [
            'table'  => 'cms_page_translations',
            'fk'     => 'page_id',
            'fields' => [
                'slug', 'title', 'excerpt', 'meta_title', 'meta_description',
                'og_image_file_id', 'og_type', 'canonical_url', 'robots', 'schema_data'
            ],
        ],
        'menu' => [
            'table'  => 'cms_menu_translations',
            'fk'     => 'menu_id',
            'fields' => ['name'],
        ],
        'menu_item' => [
            'table'  => 'cms_menu_item_translations',
            'fk'     => 'menu_item_id',
            'fields' => ['label', 'custom_url'],
        ],
        'category' => [
            'table'  => 'cms_category_translations',
            'fk'     => 'category_id',
            'fields' => ['name', 'slug', 'description'],
        ],
        'tag' => [
            'table'  => 'cms_tag_translations',
            'fk'     => 'tag_id',
            'fields' => ['name', 'slug'],
        ],
        'collection' => [
            'table'  => 'cms_collection_translations',
            'fk'     => 'collection_id',
            'fields' => ['name', 'description', 'listing_title', 'listing_intro', 'default_meta_title', 'default_meta_description'],
        ],
        'entry' => [
            'table'  => 'cms_entry_translations',
            'fk'     => 'entry_id',
            'fields' => [
                'slug', 'title', 'excerpt', 'featured_file_id', 'meta_title', 'meta_description',
                'og_image_file_id', 'og_type', 'canonical_url', 'robots', 'schema_data'
            ],
        ],
        'block_instance' => [
            'table'  => 'cms_block_instance_translations',
            'fk'     => 'instance_id',
            'fields' => ['block_data'],
        ],
        'file' => [
            'table'  => 'cms_file_translations',
            'fk'     => 'file_id',
            'fields' => ['alt_text', 'caption', 'title', 'credit', 'description'],
        ],
    ];

    /**
     * @param BaseConnection<mixed, mixed>|null $db
     */
    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Resolve translations for a resource.
     *
     * @param string $resourceType Type of resource (e.g. 'setting', 'page')
     * @param int $id The resource ID
     * @param string $langCode The target language code (e.g. 'es')
     * @return array<string, mixed> The translated payload with 'is_fallback' flag
     */
    public function resolve(string $resourceType, int $id, string $langCode): array
    {
        if (!isset(self::RESOURCE_MAP[$resourceType])) {
            throw new \InvalidArgumentException("Unsupported resource type: {$resourceType}");
        }

        $config = self::RESOURCE_MAP[$resourceType];

        // 1. Resolve target language
        $targetLang = $this->getLanguageByCode($langCode);

        // If target language is active and exists, try to get translation
        if ($targetLang && (int) $targetLang['is_active'] === 1) {
            $translation = $this->getTranslation((string) $config['table'], (string) $config['fk'], $id, (int) $targetLang['id']);
            if ($translation) {
                return array_merge($this->sanitizeFields($translation, $config['fields']), ['is_fallback' => false]);
            }
        }

        // 2. Fallback to default language
        $defaultLang = $this->getDefaultLanguage();
        if ($defaultLang) {
            $translation = $this->getTranslation((string) $config['table'], (string) $config['fk'], $id, (int) $defaultLang['id']);
            if ($translation) {
                return array_merge($this->sanitizeFields($translation, $config['fields']), ['is_fallback' => true]);
            }
        }

        // 3. Fallback to fallback language of the target language if defined
        if ($targetLang && isset($targetLang['fallback_language_id'])) {
            $translation = $this->getTranslation((string) $config['table'], (string) $config['fk'], $id, (int) $targetLang['fallback_language_id']);
            if ($translation) {
                return array_merge($this->sanitizeFields($translation, $config['fields']), ['is_fallback' => true]);
            }
        }

        // 4. Return default empty structure
        $emptyPayload = [];
        foreach ($config['fields'] as $field) {
            $emptyPayload[$field] = null;
        }

        return array_merge($emptyPayload, ['is_fallback' => true]);
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

    /**
     * @return array<string, mixed>|null
     */
    private function getTranslation(string $table, string $fkColumn, int $resourceId, int $languageId): ?array
    {
        $result = $this->db->table($table)
            ->where($fkColumn, $resourceId)
            ->where('language_id', $languageId)
            ->get();

        return $result instanceof \CodeIgniter\Database\ResultInterface ? $result->getRowArray() : null;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data, array $fields): array
    {
        $sanitized = [];
        foreach ($fields as $field) {
            $sanitized[$field] = $data[$field] ?? null;
        }
        return $sanitized;
    }
}
