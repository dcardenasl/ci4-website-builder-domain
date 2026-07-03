<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class BackfillCmsFileReferences extends BaseCommand
{
    protected $group = 'Maintenance';
    protected $name = 'cms:file-references-backfill';
    protected $description = 'Rebuild canonical CMS file references and normalize legacy file URLs.';
    protected $usage = 'cms:file-references-backfill [--dry-run]';
    protected $options = [
        '--dry-run' => 'Report the changes without writing to the database.',
    ];

    private ?FileUrlResolver $resolver = null;
    private ?FileReferenceSynchronizer $synchronizer = null;

    public function __construct(?FileUrlResolver $resolver = null, ?FileReferenceSynchronizer $synchronizer = null)
    {
        $this->resolver = $resolver;
        $this->synchronizer = $synchronizer;
    }

    public function run(array $params): void
    {
        $dryRun = in_array('--dry-run', $params, true);
        $db = Database::connect();
        $resolver = $this->resolver ?? new FileUrlResolver($db);
        $synchronizer = $this->synchronizer ?? new FileReferenceSynchronizer($db, $resolver);

        $summary = [
            'entries' => 0,
            'pages' => 0,
            'block_instances' => 0,
            'entry_rows_updated' => 0,
            'block_rows_updated' => 0,
            'unresolved_urls' => 0,
        ];

        $summary['entry_rows_updated'] = $this->normalizeEntryTranslations($db, $resolver, $dryRun);
        $summary['block_rows_updated']  = $this->normalizeBlockTranslations($db, $resolver, $dryRun);

        if (! $dryRun) {
            $rebuild = $synchronizer->rebuildAll();
            $summary['pages'] = (int) ($rebuild['pages'] ?? 0);
            $summary['entries'] = (int) ($rebuild['entries'] ?? 0);
            $summary['block_instances'] = (int) ($rebuild['block_instances'] ?? 0);
        }

        CLI::write(
            sprintf(
                'CMS file backfill %s. Entries=%d Pages=%d BlockInstances=%d EntryRowsUpdated=%d BlockRowsUpdated=%d',
                $dryRun ? 'dry-run complete' : 'complete',
                $summary['entries'],
                $summary['pages'],
                $summary['block_instances'],
                $summary['entry_rows_updated'],
                $summary['block_rows_updated']
            ),
            'green'
        );
    }

    private function normalizeEntryTranslations(\CodeIgniter\Database\BaseConnection $db, FileUrlResolver $resolver, bool $dryRun): int
    {
        $updated = 0;
        $rows = $db->table('cms_entry_translations')->get()->getResultArray();

        foreach ($rows as $row) {
            $fileId = $resolver->resolveFileIdFromValue($row['featured_file_id'] ?? null, $row['featured_image_url'] ?? null);
            $canonicalUrl = $resolver->resolveUrlValue($fileId, $row['featured_image_url'] ?? null);
            $normalizedFileId = $fileId !== null ? $fileId : (is_numeric($row['featured_file_id'] ?? null) ? (int) $row['featured_file_id'] : null);

            $payload = [
                'featured_file_id' => $normalizedFileId,
                'featured_image_url' => $canonicalUrl,
            ];

            if ((string) ($row['featured_file_id'] ?? '') !== (string) ($payload['featured_file_id'] ?? '')
                || (string) ($row['featured_image_url'] ?? '') !== (string) ($payload['featured_image_url'] ?? '')
            ) {
                $updated++;
                if (! $dryRun) {
                    $db->table('cms_entry_translations')
                        ->where('id', (int) $row['id'])
                        ->update($payload);
                }
            }
        }

        return $updated;
    }

    private function normalizeBlockTranslations(\CodeIgniter\Database\BaseConnection $db, FileUrlResolver $resolver, bool $dryRun): int
    {
        $updated = 0;
        $instances = $db->table('cms_block_instances')
            ->select('id, block_id')
            ->get()
            ->getResultArray();

        foreach ($instances as $instance) {
            $schemaFields = $this->schemaFields($db, (int) ($instance['block_id'] ?? 0));
            if ($schemaFields === []) {
                continue;
            }

            $translations = $db->table('cms_block_instance_translations')
                ->where('instance_id', (int) $instance['id'])
                ->get()
                ->getResultArray();

            foreach ($translations as $translation) {
                $blockData = $this->decodeJsonArray($translation['block_data'] ?? null);
                if ($blockData === []) {
                    continue;
                }

                $normalized = $this->normalizeBlockData($blockData, $schemaFields, $resolver);
                if ($normalized === $blockData) {
                    continue;
                }

                $updated++;
                if (! $dryRun) {
                    $db->table('cms_block_instance_translations')
                        ->where('id', (int) $translation['id'])
                        ->update([
                            'block_data' => json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                }
            }
        }

        return $updated;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function schemaFields(\CodeIgniter\Database\BaseConnection $db, int $blockId): array
    {
        if ($blockId <= 0) {
            return [];
        }

        $row = $db->table('cms_content_blocks')
            ->select('schema_definition')
            ->where('id', $blockId)
            ->get()
            ->getRowArray();

        if (! is_array($row) || ! isset($row['schema_definition'])) {
            return [];
        }

        $decoded = json_decode((string) $row['schema_definition'], true);
        if (! is_array($decoded)) {
            return [];
        }

        $fields = $decoded['fields'] ?? [];
        return is_array($fields) ? $fields : [];
    }

    /**
     * @param array<string, mixed> $blockData
     * @param array<string, array<string, mixed>> $schemaFields
     * @return array<string, mixed>
     */
    private function normalizeBlockData(array $blockData, array $schemaFields, FileUrlResolver $resolver): array
    {
        foreach ($schemaFields as $fieldKey => $fieldDef) {
            $type = strtolower((string) ($fieldDef['type'] ?? 'string'));

            if ($type === 'file') {
                $fileIdKey = $fieldKey . '_file_id';
                $urlKey = $fieldKey . '_url';
                $resolvedFileId = $resolver->resolveFileIdFromValue($blockData[$fileIdKey] ?? null, $blockData[$urlKey] ?? null);
                if ($resolvedFileId !== null) {
                    $blockData[$fileIdKey] = $resolvedFileId;
                }
                $blockData[$urlKey] = $resolver->resolveUrlValue($resolvedFileId, isset($blockData[$urlKey]) ? (string) $blockData[$urlKey] : null);
                continue;
            }

            if ($type === 'repeater') {
                $items = $blockData[$fieldKey] ?? [];
                $itemFields = is_array($fieldDef['item_fields'] ?? null) ? (array) $fieldDef['item_fields'] : [];
                if (! is_array($items) || $itemFields === []) {
                    continue;
                }

                $normalized = [];
                foreach ($items as $item) {
                    $normalized[] = is_array($item)
                        ? $this->normalizeBlockData($item, $itemFields, $resolver)
                        : $item;
                }

                $blockData[$fieldKey] = $normalized;
                continue;
            }

            if (in_array($type, ['group', 'fieldset'], true)) {
                $nestedFields = is_array($fieldDef['fields'] ?? null) ? (array) $fieldDef['fields'] : [];
                $nestedData = $blockData[$fieldKey] ?? null;
                if (is_array($nestedData) && $nestedFields !== []) {
                    $blockData[$fieldKey] = $this->normalizeBlockData($nestedData, $nestedFields, $resolver);
                }
            }
        }

        return $blockData;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonArray(mixed $value): array
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
}
