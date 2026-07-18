<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Ensures demo content is valid against the block catalog created by the same
 * bootstrap. This prevents seed data from drifting between block_data and
 * block_config or persisting obsolete field names.
 *
 * @internal
 */
final class SiteBootstrapSchemaAlignmentTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        foreach ([
            'cms_file_references',
            'cms_block_instance_translations',
            'cms_block_instances',
            'cms_content_blocks',
            'cms_form_field_translations',
            'cms_form_fields',
            'cms_form_translations',
            'cms_forms',
            'cms_form_submissions',
            'cms_entry_categories',
            'cms_entry_tags',
            'cms_entry_translations',
            'cms_entries',
            'cms_category_translations',
            'cms_categories',
            'cms_tag_translations',
            'cms_tags',
            'cms_menu_item_translations',
            'cms_menu_items',
            'cms_menus',
            'cms_page_translations',
            'cms_pages',
            'cms_collection_translations',
            'cms_collections',
            'cms_setting_translations',
            'cms_settings',
            'cms_languages',
        ] as $table) {
            $this->db->query("DELETE FROM `{$table}`");
        }
        $this->db->enableForeignKeyChecks();
    }

    public function testEverySeededBlockPayloadMatchesItsDeclaredSchema(): void
    {
        \Config\Database::seeder()->call(\App\Database\Seeds\SiteBootstrapSeeder::class);

        $instances = $this->db->table('cms_block_instances')
            ->select('cms_block_instances.id, cms_block_instances.block_config, cms_content_blocks.block_key, cms_content_blocks.schema_definition')
            ->join('cms_content_blocks', 'cms_content_blocks.id = cms_block_instances.block_id')
            ->get()
            ->getResultArray();

        $this->assertNotEmpty($instances);

        foreach ($instances as $instance) {
            $instanceId = (int) $instance['id'];
            $blockKey = (string) $instance['block_key'];
            $schema = $this->decodeObject((string) $instance['schema_definition'], "schema for {$blockKey}");
            $configFields = is_array($schema['config_fields'] ?? null) ? $schema['config_fields'] : [];
            $dataFields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
            $config = $this->decodeObject((string) ($instance['block_config'] ?? ''), "config for {$blockKey}#{$instanceId}");

            $this->assertPayloadMatchesFields($config, $configFields, "{$blockKey}#{$instanceId} block_config");

            $translations = $this->db->table('cms_block_instance_translations')
                ->select('language_id, block_data')
                ->where('instance_id', $instanceId)
                ->get()
                ->getResultArray();

            $requiredDataFields = array_filter(
                $dataFields,
                static fn (mixed $definition): bool => is_array($definition) && ($definition['required'] ?? false) === true,
            );
            if ($requiredDataFields !== []) {
                $this->assertNotEmpty($translations, "{$blockKey}#{$instanceId} requires localized data but has no translations.");
            }

            foreach ($translations as $translation) {
                $languageId = (int) $translation['language_id'];
                $data = $this->decodeObject((string) ($translation['block_data'] ?? ''), "data for {$blockKey}#{$instanceId}");
                $this->assertPayloadMatchesFields($data, $dataFields, "{$blockKey}#{$instanceId} block_data language {$languageId}");
            }
        }

        $this->assertDemoContentContainsNoTemplateTokens();
        $this->assertSocialSettingsContainValidUrls();
    }

    private function assertDemoContentContainsNoTemplateTokens(): void
    {
        foreach ([
            'cms_settings' => ['setting_key', 'setting_value'],
            'cms_page_translations' => ['slug', 'title', 'excerpt', 'meta_title', 'meta_description'],
            'cms_block_instance_translations' => ['block_data'],
        ] as $table => $columns) {
            $rows = $this->db->table($table)->select($columns)->get()->getResultArray();
            foreach ($rows as $row) {
                $serialized = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $this->assertIsString($serialized);
                $this->assertDoesNotMatchRegularExpression(
                    '/\[(?:TU_[^]]+|YOUR_[^]]+|SOCIAL_[^]]+)\]/u',
                    $serialized,
                    "{$table} contains an unresolved demo template token.",
                );
            }
        }
    }

    private function assertSocialSettingsContainValidUrls(): void
    {
        $rows = $this->db->table('cms_settings')
            ->select('setting_key, setting_value')
            ->where('setting_group', 'social')
            ->get()
            ->getResultArray();

        $this->assertCount(8, $rows);
        foreach ($rows as $row) {
            $url = (string) ($row['setting_value'] ?? '');
            $this->assertNotFalse(filter_var($url, FILTER_VALIDATE_URL), (string) ($row['setting_key'] ?? 'social setting'));
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $fields
     */
    private function assertPayloadMatchesFields(array $payload, array $fields, string $context): void
    {
        foreach ($payload as $key => $value) {
            $this->assertArrayHasKey($key, $fields, "{$context} contains undeclared field '{$key}'.");
            $definition = is_array($fields[$key]) ? $fields[$key] : [];
            $this->assertFieldValue($value, $definition, "{$context}.{$key}");
        }

        foreach ($fields as $key => $definition) {
            if (! is_array($definition) || ($definition['required'] ?? false) !== true) {
                continue;
            }

            $this->assertArrayHasKey($key, $payload, "{$context} is missing required field '{$key}'.");
            $this->assertFalse($this->isEmptyValue($payload[$key]), "{$context}.{$key} must not be empty.");
        }
    }

    /** @param array<string, mixed> $definition */
    private function assertFieldValue(mixed $value, array $definition, string $context): void
    {
        $type = (string) ($definition['type'] ?? '');
        if ($type === 'media_reference') {
            $this->assertIsArray($value, "{$context} must be a media_reference object.");
            $this->assertSame(['file_id', 'source_kind', 'url'], $this->sortedKeys($value), "{$context} must use the exact canonical media keys.");
            $this->assertContains($value['source_kind'], ['hub_file', 'external_url'], "{$context} has an invalid source_kind.");
            $this->assertTrue($value['file_id'] === null || (is_int($value['file_id']) && $value['file_id'] > 0), "{$context}.file_id must be null or a positive integer.");
            $this->assertIsString($value['url'], "{$context}.url must be a string.");

            if ($value['source_kind'] === 'hub_file') {
                $this->assertIsInt($value['file_id'], "{$context} requires file_id for hub_file.");
            } else {
                $this->assertNull($value['file_id'], "{$context} external_url must not carry file_id.");
                $this->assertNotSame('', trim($value['url']), "{$context} external_url requires url.");
            }
        }

        if ($type !== 'repeater' || ! is_array($value)) {
            return;
        }

        $itemFields = is_array($definition['item_fields'] ?? null) ? $definition['item_fields'] : [];
        foreach ($value as $index => $item) {
            $this->assertIsArray($item, "{$context}[{$index}] must be an object.");
            $this->assertPayloadMatchesFields($item, $itemFields, "{$context}[{$index}]");
        }
    }

    /** @return array<string, mixed> */
    private function decodeObject(string $json, string $context): array
    {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded, "Invalid JSON object in {$context}.");

        return $decoded;
    }

    /** @param array<string, mixed> $value @return list<string> */
    private function sortedKeys(array $value): array
    {
        $keys = array_keys($value);
        sort($keys);

        return $keys;
    }

    private function isEmptyValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
