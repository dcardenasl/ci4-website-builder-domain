<?php

declare(strict_types=1);

namespace App\Database\Seeds\Concerns;

use CodeIgniter\Database\Exceptions\DatabaseException;

trait IdempotentSeederSupport
{
    /**
     * Upsert a row using a deterministic lookup set.
     *
     * The method prefers updating an existing row, falls back to inserting when
     * needed, and tolerates race/uniqueness collisions by re-reading the row and
     * updating it in place. It returns the primary key when the table exposes an
     * `id` column, or null otherwise.
     *
     * @param array<string, scalar|null> $lookup
     * @param array<string, mixed>        $data
     */
    protected function upsertRecord(string $table, array $lookup, array $data): ?int
    {
        $supportsId = $this->db->fieldExists('id', $table);
        $supportsCreatedAt = $this->db->fieldExists('created_at', $table);
        $supportsUpdatedAt = $this->db->fieldExists('updated_at', $table);

        $existing = $this->db->table($table)
            ->where($lookup)
            ->get()
            ->getRowArray();

        $payload = array_merge($lookup, $data);

        if ($supportsUpdatedAt) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
        }

        if ($existing === null) {
            if ($supportsCreatedAt) {
                $payload['created_at'] = date('Y-m-d H:i:s');
            }

            try {
                $this->db->table($table)->insert($payload);

                return $supportsId ? (int) $this->db->insertID() : null;
            } catch (DatabaseException) {
                $fallback = $this->db->table($table)
                    ->where($lookup)
                    ->get()
                    ->getRowArray();

                if ($fallback !== null) {
                    if (isset($fallback['id'])) {
                        $this->db->table($table)
                            ->where('id', (int) $fallback['id'])
                            ->update($payload);

                        return (int) $fallback['id'];
                    }

                    $this->db->table($table)
                        ->where($lookup)
                        ->update($payload);

                    return null;
                }

                return null;
            }
        }

        $updatePayload = $payload;
        unset($updatePayload['created_at']);

        if (isset($existing['id'])) {
            $id = (int) $existing['id'];
            $this->db->table($table)
                ->where('id', $id)
                ->update($updatePayload);

            return $id;
        }

        $this->db->table($table)
            ->where($lookup)
            ->update($updatePayload);

        return null;
    }

    /**
     * Insert a new row and return its primary key when available.
     *
     * This is for records whose natural key is derived elsewhere and should not
     * be upserted directly by a lookup set.
     *
     * @param array<string, mixed> $data
     */
    protected function createRecord(string $table, array $data): ?int
    {
        $supportsId = $this->db->fieldExists('id', $table);
        $supportsCreatedAt = $this->db->fieldExists('created_at', $table);
        $supportsUpdatedAt = $this->db->fieldExists('updated_at', $table);

        if ($supportsCreatedAt && ! array_key_exists('created_at', $data)) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        if ($supportsUpdatedAt && ! array_key_exists('updated_at', $data)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table($table)->insert($data);

        return $supportsId ? (int) $this->db->insertID() : null;
    }

    /**
     * Upsert the canonical collection index page for a collection.
     *
     * @param array<string, mixed> $data
     */
    protected function upsertCollectionIndexPageRecord(int $collectionId, array $data): ?int
    {
        return $this->upsertRecord('cms_pages', [
            'page_type'     => 'collection_index',
            'collection_id' => $collectionId,
        ], $data);
    }

    /**
     * Delete every block instance (and its translations) owned by a page, so
     * a re-run of the seeder can insert a clean set instead of accumulating
     * duplicates. Extracted from 8 page seeders that each carried a
     * byte-identical copy (2026-07-15 hygiene cleanup, DEBT-001).
     */
    protected function resetPageBlocks(int $pageId): void
    {
        $instanceIds = $this->db->table('cms_block_instances')
            ->select('id')
            ->where('owner_type', 'page')
            ->where('owner_id', $pageId)
            ->get()
            ->getResultArray();

        if ($instanceIds === []) {
            return;
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $instanceIds);
        $this->db->table('cms_block_instance_translations')->whereIn('instance_id', $ids)->delete();
        $this->db->table('cms_block_instances')->whereIn('id', $ids)->delete();
    }

    /**
     * Build a canonical media reference payload for seed data.
     *
     * Seeders should persist the same nested shape the CMS renderer expects:
     * `{source_kind, file_id, url}`. That keeps demo content aligned with the
     * runtime contract.
     *
     * @return array{source_kind: string, file_id: int|null, url: string}
     */
    protected function mediaReference(string $url, ?int $fileId = null): array
    {
        $url = trim($url);

        if ($fileId !== null) {
            return [
                'source_kind' => 'hub_file',
                'file_id' => $fileId,
                'url' => $url !== '' ? $url : '/files/' . $fileId . '/view',
            ];
        }

        return [
            'source_kind' => 'external_url',
            'file_id' => null,
            'url' => $url,
        ];
    }

    /**
     * Convert a canonical media reference into the relational columns used by
     * seeded translation rows.
     *
     * @param array{source_kind: string, file_id: int|null, url: string}|null $reference
     * @return array<string, int|string|null>
     */
    protected function mediaReferenceColumns(
        ?array $reference,
        string $fileIdColumn,
        string $urlColumn
    ): array {
        if ($reference === null) {
            return [
                $fileIdColumn => null,
                $urlColumn    => null,
            ];
        }

        if (! array_key_exists('source_kind', $reference)
            || ! array_key_exists('file_id', $reference)
            || ! array_key_exists('url', $reference)
            || ! is_string($reference['source_kind'])
            || ($reference['file_id'] !== null && ! is_int($reference['file_id']))
            || ! is_string($reference['url'])) {
            throw new \LogicException('Seeder media references must define source_kind, file_id, and url with canonical types.');
        }

        $sourceKind = $reference['source_kind'];
        $fileId = $reference['file_id'];
        $url = trim($reference['url']);

        if (! in_array($sourceKind, ['hub_file', 'external_url'], true)
            || ($sourceKind === 'hub_file' && ($fileId === null || $fileId <= 0))
            || ($sourceKind === 'external_url' && ($fileId !== null || $url === ''))) {
            throw new \LogicException('Seeder media references must use the canonical {source_kind, file_id, url} contract.');
        }

        return [
            $fileIdColumn => $fileId,
            $urlColumn    => $url !== '' ? $url : null,
        ];
    }
}
