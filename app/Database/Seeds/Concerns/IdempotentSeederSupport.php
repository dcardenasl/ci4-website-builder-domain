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
     * Upsert a collection index page while repairing legacy starter rows in place.
     *
     * This keeps the current canonical shape (`page_type = collection_index`
     * with a `collection_id`) but avoids duplicating an older singleton page
     * when an existing starter database still has the legacy type.
     *
     * @param list<string> $legacyPageTypes
     * @param array<string, mixed> $data
     */
    protected function upsertCollectionIndexPageRecord(int $collectionId, array $legacyPageTypes, array $data): ?int
    {
        $existing = $this->db->table('cms_pages')
            ->where('page_type', 'collection_index')
            ->where('collection_id', $collectionId)
            ->get()
            ->getRowArray();

        if ($existing !== null && isset($existing['id'])) {
            $pageId = (int) $existing['id'];
            $updatePayload = array_merge($data, [
                'page_type'     => 'collection_index',
                'collection_id' => $collectionId,
            ]);
            if ($this->db->fieldExists('updated_at', 'cms_pages')) {
                $updatePayload['updated_at'] = date('Y-m-d H:i:s');
            }

            $this->db->table('cms_pages')
                ->where('id', $pageId)
                ->update($updatePayload);

            return $pageId;
        }

        if ($legacyPageTypes !== []) {
            $legacyPage = $this->db->table('cms_pages')
                ->whereIn('page_type', $legacyPageTypes)
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();

            if ($legacyPage !== null && isset($legacyPage['id'])) {
                $pageId = (int) $legacyPage['id'];
                $updatePayload = array_merge($data, [
                    'page_type'     => 'collection_index',
                    'collection_id' => $collectionId,
                ]);
                if ($this->db->fieldExists('updated_at', 'cms_pages')) {
                    $updatePayload['updated_at'] = date('Y-m-d H:i:s');
                }

                $this->db->table('cms_pages')
                    ->where('id', $pageId)
                    ->update($updatePayload);

                return $pageId;
            }
        }

        return $this->upsertRecord('cms_pages', [
            'page_type'     => 'collection_index',
            'collection_id' => $collectionId,
        ], $data);
    }
}
