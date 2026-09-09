<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use App\DTO\Request\Editor\EditorOwnerDTO;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\ResultInterface;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;

/** Reads all document tables on the caller's connection and transaction snapshot. */
final class EditorDocumentSnapshotReader
{
    /** @param BaseConnection<mixed, mixed> $db */
    public function __construct(private readonly BaseConnection $db)
    {
    }

    public function read(EditorOwnerDTO $owner, bool $lock = false): EditorDocumentSnapshot
    {
        $table = $owner->type === 'page' ? 'cms_pages' : 'cms_entries';
        if ($lock && $this->db->transDepth === 0) {
            throw new \LogicException('Editor locks require an active transaction.');
        }
        if ($lock && $this->db->DBDriver === 'SQLite3') {
            // A deferred SQLite transaction must acquire its write lock before reading.
            $this->db->table($table)->where('id', $owner->id)->set('id', 'id', false)->update();
        }
        $writeLock = $lock && $this->db->DBDriver === 'MySQLi' ? ' FOR UPDATE' : '';
        $readLock = $lock && $this->db->DBDriver === 'MySQLi' ? ' LOCK IN SHARE MODE' : '';
        $rows = $this->rows($table, ['id' => $owner->id, 'deleted_at' => null], $writeLock);
        $record = $rows[0] ?? null;
        if ($record === null) {
            throw new NotFoundException(lang('Api.resourceNotFound'));
        }
        $instances = $this->rows('cms_block_instances', ['owner_type' => $owner->type, 'owner_id' => $owner->id], $writeLock);
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $instances);
        $translations = [];
        if ($ids !== []) {
            $translations = $this->select($this->db->table('cms_block_instance_translations')->whereIn('instance_id', $ids)->orderBy('id'), $writeLock);
        }
        $collectionId = isset($record['collection_id']) ? (int) $record['collection_id'] : 0;
        $collection = $owner->type === 'entry' && $collectionId > 0
            ? ($this->rows('cms_collections', ['id' => $collectionId], $readLock)[0] ?? []) : [];

        return new EditorDocumentSnapshot(
            $record,
            $this->rows($owner->type === 'page' ? 'cms_page_translations' : 'cms_entry_translations', [$owner->type . '_id' => $owner->id], $writeLock),
            $instances,
            $translations,
            $this->rows('cms_content_blocks', [], $readLock),
            $this->rows('cms_languages', [], $readLock),
            $collection,
        );
    }

    /**
     * @param array<string, int|string|null> $where
     * @return list<array<string, mixed>>
     */
    private function rows(string $table, array $where = [], string $lock = ''): array
    {
        return $this->select($this->db->table($table)->where($where)->orderBy('id'), $lock);
    }

    /** @return list<array<string, mixed>> */
    private function select(BaseBuilder $builder, string $lock): array
    {
        $query = $this->db->query($builder->getCompiledSelect() . $lock);
        if (! $query instanceof ResultInterface) {
            throw new \RuntimeException('Cannot read editor document.');
        }

        return array_values($query->getResultArray());
    }
}
