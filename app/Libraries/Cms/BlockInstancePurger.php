<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/** Remove all block instances owned by a page or entry before their references become orphaned. */
class BlockInstancePurger
{
    /** @var BaseConnection<mixed, mixed> */
    private BaseConnection $database;

    /**
     * @param BaseConnection<mixed, mixed>|null $database
     */
    public function __construct(?BaseConnection $database = null)
    {
        $this->database = $database ?? Database::connect();
    }

    /** @return int number of block instances purged */
    public function purgeForOwner(string $ownerType, int $ownerId): int
    {
        if (! in_array($ownerType, ['page', 'entry'], true) || $ownerId <= 0) {
            return 0;
        }

        $result = $this->database->table('cms_block_instances')
            ->select('id')
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->get();
        $rows = $result === false ? [] : $result->getResultArray();

        if ($rows === []) {
            return 0;
        }

        $instanceIds = array_values(array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $rows
        ));
        $instanceIds = array_values(array_filter($instanceIds, static fn (int $id): bool => $id > 0));

        if ($instanceIds === []) {
            return 0;
        }

        $this->database->table('cms_block_instance_translations')
            ->whereIn('instance_id', $instanceIds)
            ->delete();
        $this->database->table('cms_block_instances')
            ->whereIn('id', $instanceIds)
            ->delete();

        log_message(
            'info',
            '[BlockInstancePurger] Purged ' . count($instanceIds) . " block instance(s) for owner_type={$ownerType} owner_id={$ownerId}."
        );

        return count($instanceIds);
    }
}
