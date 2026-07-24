<?php

declare(strict_types=1);

namespace Tests\Support\Fixtures;

use CodeIgniter\Database\BaseConnection;

final class CmsFixtureFactory
{
    private FixtureValueFactory $values;

    public function __construct(
        private readonly BaseConnection $db,
        string $scope = 'case',
    ) {
        $this->values = new FixtureValueFactory($scope);
    }

    /**
     * @return list<array{id: int, code: string, name: string, is_default: bool}>
     */
    public function languages(int $count = 2, int $defaultPosition = 0): array
    {
        $languages = [];

        for ($position = 0; $position < $count; $position++) {
            $code = $this->values->locale($position);
            $name = $this->values->text('language', $code);
            $isDefault = $position === $defaultPosition;

            $this->db->table('cms_languages')->insert([
                'code'       => $code,
                'name'       => $name,
                'native_name' => $name,
                'is_default' => $isDefault ? 1 : 0,
                'is_active'  => 1,
                'sort_order' => $position,
            ]);

            $languages[] = [
                'id'         => (int) $this->db->insertID(),
                'code'       => $code,
                'name'       => $name,
                'is_default' => $isDefault,
            ];
        }

        return $languages;
    }

    /**
     * @param list<array<string, mixed>> $translations
     * @return array{id: int, key: string, translations: list<array<string, mixed>>}
     */
    public function collection(array $translations = [], array $overrides = []): array
    {
        $key = (string) ($overrides['collection_key'] ?? $this->values->slug('collection'));
        $payload = array_replace([
            'collection_key' => $key,
            'collection_type' => 'other',
            'is_active' => 1,
            'requires_approval' => 0,
            'enables_categories' => 1,
            'enables_tags' => 1,
            'sort_order' => 0,
        ], $overrides);

        $this->db->table('cms_collections')->insert($payload);
        $id = (int) $this->db->insertID();

        foreach ($translations as $translation) {
            $this->db->table('cms_collection_translations')->insert(array_replace([
                'collection_id' => $id,
                'language_id' => 0,
                'slug' => $this->values->slug('collection-slug'),
                'name' => $this->values->text('collection-name'),
            ], $translation));
        }

        return ['id' => $id, 'key' => $key, 'translations' => $translations];
    }

    /**
     * @param list<array<string, mixed>> $translations
     * @return array{id: int, key: string, translations: list<array<string, mixed>>}
     */
    public function menu(array $translations = [], array $overrides = []): array
    {
        $key = (string) ($overrides['menu_key'] ?? $this->values->slug('menu'));
        $payload = array_replace([
            'menu_key' => $key,
            'location' => 'fixture',
            'is_active' => 1,
        ], $overrides);

        $this->db->table('cms_menus')->insert($payload);
        $id = (int) $this->db->insertID();

        foreach ($translations as $translation) {
            $this->db->table('cms_menu_translations')->insert(array_replace([
                'menu_id' => $id,
                'language_id' => 0,
                'name' => $this->values->text('menu-name'),
            ], $translation));
        }

        return ['id' => $id, 'key' => $key, 'translations' => $translations];
    }

    /**
     * @param array<string, mixed> $overrides
     * @param list<array<string, mixed>> $translations
     * @return array{id: int, translations: list<array<string, mixed>>}
     */
    public function page(array $translations = [], array $overrides = []): array
    {
        $this->db->table('cms_pages')->insert(array_replace([
            'status' => 'published',
            'page_type' => 'generic',
            'sort_order' => 0,
            'is_in_sitemap' => 1,
        ], $overrides));
        $id = (int) $this->db->insertID();

        foreach ($translations as $translation) {
            $this->db->table('cms_page_translations')->insert(array_replace([
                'page_id' => $id,
                'language_id' => 0,
                'slug' => $this->values->slug('page-slug'),
                'title' => $this->values->text('page-title'),
            ], $translation));
        }

        return ['id' => $id, 'translations' => $translations];
    }

    /**
     * @param array<string, mixed> $overrides
     * @param list<array<string, mixed>> $translations
     * @return array{id: int, key: string, translations: list<array<string, mixed>>}
     */
    public function form(array $translations = [], array $overrides = []): array
    {
        $key = (string) ($overrides['form_key'] ?? $this->values->slug('form'));
        $this->db->table('cms_forms')->insert(array_replace([
            'form_key' => $key,
            'is_active' => 1,
            'has_captcha' => 0,
        ], $overrides));
        $id = (int) $this->db->insertID();

        foreach ($translations as $translation) {
            $this->db->table('cms_form_translations')->insert(array_replace([
                'form_id' => $id,
                'language_id' => 0,
                'name' => $this->values->text('form-name'),
                'submit_label' => $this->values->text('form-submit'),
            ], $translation));
        }

        return ['id' => $id, 'key' => $key, 'translations' => $translations];
    }

    /**
     * @param list<array<string, mixed>> $translations
     * @param array<string, mixed> $overrides
     * @return array{id: int, translations: list<array<string, mixed>>}
     */
    public function entry(int $collectionId, array $translations = [], array $overrides = []): array
    {
        $this->db->table('cms_entries')->insert(array_replace([
            'collection_id' => $collectionId,
            'workflow_status' => 'published',
            'is_featured' => 0,
            'view_count' => 0,
            'sort_order' => 0,
            'is_in_sitemap' => 1,
        ], $overrides));
        $id = (int) $this->db->insertID();

        foreach ($translations as $translation) {
            $this->db->table('cms_entry_translations')->insert(array_replace([
                'entry_id' => $id,
                'language_id' => 0,
                'slug' => $this->values->slug('entry-slug'),
                'title' => $this->values->text('entry-title'),
            ], $translation));
        }

        return ['id' => $id, 'translations' => $translations];
    }

    /** @param array<string, mixed> $overrides */
    public function category(int $collectionId, array $overrides = []): array
    {
        $this->db->table('cms_categories')->insert(array_replace([
            'collection_id' => $collectionId,
            'sort_order' => 0,
            'is_active' => 1,
        ], $overrides));

        return ['id' => (int) $this->db->insertID()];
    }

    /** @param array<string, mixed> $overrides */
    public function tag(array $overrides = []): array
    {
        $this->db->table('cms_tags')->insert(array_replace([
            'is_active' => 1,
        ], $overrides));

        return ['id' => (int) $this->db->insertID()];
    }

    /** @param array<string, mixed> $overrides */
    public function block(int $blockId, string $ownerType, int $ownerId, array $overrides = []): array
    {
        $this->db->table('cms_block_instances')->insert(array_replace([
            'block_id' => $blockId,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'parent_instance_id' => null,
            'sort_order' => 0,
            'column_index' => null,
            'is_active' => 1,
            'block_config' => json_encode([], JSON_THROW_ON_ERROR),
        ], $overrides));

        return ['id' => (int) $this->db->insertID()];
    }

    public function text(string $role, string $variant = ''): string
    {
        return $this->values->text($role, $variant);
    }

    public function slug(string $role, string $variant = ''): string
    {
        return $this->values->slug($role, $variant);
    }
}
