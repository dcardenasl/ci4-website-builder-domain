<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Interfaces\Cms\PageQualityServiceInterface;
use App\Models\BlockInstanceModel;
use App\Models\LanguageModel;
use App\Models\PageModel;
use App\Models\PageTranslationModel;
use CodeIgniter\Entity\Entity;

final class PageQualityService implements PageQualityServiceInterface
{
    private const VERSION = 'page-quality.v2';
    private const META_TITLE_MAX = 60;
    private const META_DESCRIPTION_MAX = 160;

    public function __construct(
        private readonly PageModel $pageModel,
        private readonly PageTranslationModel $translationModel,
        private readonly LanguageModel $languageModel,
        private readonly BlockInstanceModel $blockInstanceModel,
    ) {
    }

    /** @return array<string, mixed> */
    public function analyze(int $pageId): array
    {
        $page = $this->pageModel->find($pageId);
        if (! $page instanceof Entity) {
            return [
                'version' => self::VERSION,
                'status' => 'blocked',
                'score' => 0,
                'summary' => ['errors' => 1, 'warnings' => 0, 'passed' => 0],
                'rules' => $this->rules(),
                'checks' => [[
                    'key' => 'page_exists',
                    'status' => 'fail',
                    'severity' => 'error',
                    'message_key' => 'page_not_found',
                ]],
            ];
        }

        $languages = $this->objectRows($this->languageModel
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll());
        $translations = $this->objectRows($this->translationModel
            ->where('page_id', $pageId)
            ->findAll());
        /** @var array<int, object> $translationByLanguage */
        $translationByLanguage = [];
        foreach ($translations as $translation) {
            $translationByLanguage[(int) ($translation->language_id ?? 0)] = $translation;
        }

        $defaultLanguage = null;
        foreach ($languages as $language) {
            if ((bool) ($language->is_default ?? false)) {
                $defaultLanguage = $language;
                break;
            }
        }
        $defaultLanguage ??= $languages[0] ?? null;

        $blocks = $this->blockInstances($pageId);
        $checks = [];

        $this->checkDefaultTranslation($checks, $defaultLanguage, $translationByLanguage);
        $this->checkTranslations($checks, $languages, $translationByLanguage, (int) ($defaultLanguage->id ?? 0));
        $this->checkBlocks($checks, $blocks);
        $this->checkSitemapAndRobots($checks, $page, $defaultLanguage, $translationByLanguage);

        $errors = count(array_filter(
            $checks,
            static fn (array $check): bool => ($check['status'] ?? '') === 'fail' && ($check['severity'] ?? '') === 'error'
        ));
        $warnings = count(array_filter($checks, static fn (array $check): bool => ($check['status'] ?? '') === 'warning'));
        $passed = count(array_filter($checks, static fn (array $check): bool => ($check['status'] ?? '') === 'pass'));
        $total = $errors + $warnings + $passed;

        return [
            'version' => self::VERSION,
            'status' => $errors > 0 ? 'blocked' : ($warnings > 0 ? 'warning' : 'ready'),
            'score' => $total > 0 ? (int) round(($passed / $total) * 100) : 0,
            'summary' => ['errors' => $errors, 'warnings' => $warnings, 'passed' => $passed],
            'rules' => $this->rules(),
            'checks' => array_values($checks),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     * @param array<int, object> $translationByLanguage
     */
    private function checkDefaultTranslation(array &$checks, ?object $defaultLanguage, array $translationByLanguage): void
    {
        $languageId = (int) ($defaultLanguage->id ?? 0);
        $translation = $translationByLanguage[$languageId] ?? null;
        $languageCode = strtoupper((string) ($defaultLanguage->code ?? ''));

        $this->addPresenceCheck($checks, 'default_title', 'title', trim((string) ($translation->title ?? '')) !== '', 'default_title_required', $languageId, $languageCode);
        $this->addPresenceCheck($checks, 'default_slug', 'slug', trim((string) ($translation->slug ?? '')) !== '', 'default_slug_required', $languageId, $languageCode);
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     * @param list<object> $languages
     * @param array<int, object> $translationByLanguage
     */
    private function checkTranslations(array &$checks, array $languages, array $translationByLanguage, int $defaultLanguageId): void
    {
        foreach ($languages as $language) {
            $languageId = (int) ($language->id ?? 0);
            $languageCode = strtoupper((string) ($language->code ?? ''));
            $translation = $translationByLanguage[$languageId] ?? null;

            if ($languageId !== $defaultLanguageId) {
                $this->addPresenceCheck($checks, "translation_title_{$languageId}", 'title', trim((string) ($translation->title ?? '')) !== '', 'translation_title_missing', $languageId, $languageCode, 'warning');
                $this->addPresenceCheck($checks, "translation_slug_{$languageId}", 'slug', trim((string) ($translation->slug ?? '')) !== '', 'translation_slug_missing', $languageId, $languageCode, 'warning');
            }

            $this->addPresenceCheck($checks, "meta_title_presence_{$languageId}", 'meta_title', trim((string) ($translation->meta_title ?? '')) !== '', 'meta_title_missing', $languageId, $languageCode, 'warning');
            $this->addPresenceCheck($checks, "meta_description_presence_{$languageId}", 'meta_description', trim((string) ($translation->meta_description ?? '')) !== '', 'meta_description_missing', $languageId, $languageCode, 'warning', ['limit' => self::META_DESCRIPTION_MAX]);

            $this->addLengthCheck($checks, "meta_title_length_{$languageId}", 'meta_title', (string) ($translation->meta_title ?? ''), self::META_TITLE_MAX, 'meta_title_too_long', $languageId, $languageCode);
            $this->addLengthCheck($checks, "meta_description_length_{$languageId}", 'meta_description', (string) ($translation->meta_description ?? ''), self::META_DESCRIPTION_MAX, 'meta_description_too_long', $languageId, $languageCode);

            $schemaData = trim((string) ($translation->schema_data ?? ''));
            $schemaValid = $schemaData === '' || (json_decode($schemaData, true) !== null || json_last_error() === JSON_ERROR_NONE);
            $checks[] = [
                'key' => "schema_data_{$languageId}",
                'status' => $schemaValid ? 'pass' : 'fail',
                'severity' => $schemaValid ? 'info' : 'error',
                'message_key' => $schemaValid ? 'schema_data_valid' : 'schema_data_invalid',
                'field' => 'schema_data',
                'language_id' => $languageId,
                'language_code' => $languageCode,
                'message_params' => ['language_code' => $languageCode],
            ];

            $hasOgImage = trim((string) ($translation->og_image_url ?? '')) !== '' || (int) ($translation->og_image_file_id ?? 0) > 0;
            $checks[] = [
                'key' => "og_image_{$languageId}",
                'status' => $hasOgImage ? 'pass' : 'warning',
                'severity' => 'warning',
                'message_key' => $hasOgImage ? 'og_image_configured' : 'og_image_missing',
                'field' => 'og_image',
                'language_id' => $languageId,
                'language_code' => $languageCode,
                'message_params' => ['language_code' => $languageCode],
            ];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     * @param list<array<string, mixed>> $blocks
     */
    private function checkBlocks(array &$checks, array $blocks): void
    {
        $headingOwners = [];
        foreach ($blocks as $block) {
            $schema = $this->decodeArray($block['schema_definition'] ?? null);
            $presentation = is_array($schema['presentation'] ?? null) ? $schema['presentation'] : [];
            if (($presentation['owns_page_heading'] ?? false) === true) {
                $headingOwners[] = ['id' => (int) ($block['id'] ?? 0), 'block_key' => (string) ($block['block_key'] ?? '')];
            }
        }

        $ownerCount = count($headingOwners);
        $checks[] = [
            'key' => 'page_heading_owner',
            'status' => $ownerCount === 1 ? 'pass' : 'fail',
            'severity' => $ownerCount === 1 ? 'info' : 'error',
            'message_key' => $ownerCount === 0 ? 'page_heading_missing' : ($ownerCount > 1 ? 'page_heading_multiple' : 'page_heading_configured'),
            'field' => 'blocks',
            'heading_owners' => $headingOwners,
        ];
        $checks[] = [
            'key' => 'active_blocks',
            'status' => $blocks !== [] ? 'pass' : 'fail',
            'severity' => 'error',
            'message_key' => $blocks !== [] ? 'active_blocks_configured' : 'active_blocks_missing',
            'field' => 'blocks',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     * @param array<int, object> $translationByLanguage
     */
    private function checkSitemapAndRobots(array &$checks, object $page, ?object $defaultLanguage, array $translationByLanguage): void
    {
        $translation = $translationByLanguage[(int) ($defaultLanguage->id ?? 0)] ?? null;
        $robots = strtolower(trim((string) ($translation->robots ?? '')));
        $isIndexable = (bool) ($page->is_in_sitemap ?? false);
        $hasNoIndex = str_contains($robots, 'noindex');
        $consistent = ! $isIndexable || ! $hasNoIndex;

        $checks[] = [
            'key' => 'sitemap_robots_consistency',
            'status' => $consistent ? 'pass' : 'fail',
            'severity' => 'error',
            'message_key' => $consistent ? 'sitemap_robots_consistent' : 'sitemap_robots_inconsistent',
            'field' => 'robots',
            'language_id' => (int) ($defaultLanguage->id ?? 0),
            'language_code' => strtoupper((string) ($defaultLanguage->code ?? '')),
        ];
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'version' => self::VERSION,
            'heading_owner_count' => ['min' => 1, 'max' => 1],
            'meta_title' => ['max_length' => self::META_TITLE_MAX],
            'meta_description' => ['max_length' => self::META_DESCRIPTION_MAX],
            'sitemap_robots' => ['sitemap_pages_must_not_declare_noindex' => true],
            'json_ld' => ['must_be_valid_json_when_present' => true],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     * @param array<string, mixed> $messageParams
     */
    private function addPresenceCheck(array &$checks, string $key, string $field, bool $present, string $missingMessageKey, int $languageId, string $languageCode, string $missingSeverity = 'error', array $messageParams = []): void
    {
        $checks[] = [
            'key' => $key,
            'status' => $present ? 'pass' : ($missingSeverity === 'warning' ? 'warning' : 'fail'),
            'severity' => $missingSeverity,
            'message_key' => $present ? 'field_configured' : $missingMessageKey,
            'field' => $field,
            'language_id' => $languageId,
            'language_code' => $languageCode,
            'message_params' => array_merge(['language_code' => $languageCode], $messageParams),
        ];
    }

    /** @param array<int, array<string, mixed>> $checks */
    private function addLengthCheck(array &$checks, string $key, string $field, string $value, int $limit, string $tooLongMessageKey, int $languageId, string $languageCode): void
    {
        $length = mb_strlen(trim($value));
        $checks[] = [
            'key' => $key,
            'status' => $length <= $limit ? 'pass' : 'fail',
            'severity' => 'error',
            'message_key' => $length <= $limit ? 'field_length_valid' : $tooLongMessageKey,
            'field' => $field,
            'language_id' => $languageId,
            'language_code' => $languageCode,
            'message_params' => ['language_code' => $languageCode, 'limit' => $limit],
            'length' => $length,
            'limit' => $limit,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function blockInstances(int $pageId): array
    {
        $query = $this->blockInstanceModel->builder()
            ->select('i.*, b.block_key, b.schema_definition')
            ->from('cms_block_instances i')
            ->join('cms_content_blocks b', 'b.id = i.block_id')
            ->where('i.owner_type', 'page')
            ->where('i.owner_id', $pageId)
            ->where('i.is_active', 1)
            ->orderBy('i.sort_order', 'ASC')
            ->get();

        if ($query === false) {
            return [];
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $query->getResultArray();

        return $rows;
    }

    /** @return array<string, mixed> */
    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<int, mixed> $rows
     * @return list<object>
     */
    private function objectRows(array $rows): array
    {
        return array_values(array_filter($rows, static fn (mixed $row): bool => is_object($row)));
    }
}
