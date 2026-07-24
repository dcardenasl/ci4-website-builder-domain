<?php

declare(strict_types=1);

namespace App\Services\Cms;

use CodeIgniter\Database\BaseConnection;

/**
 * Reports all places within the Domain CMS where a given Hub file ID is referenced.
 * Each result follows the shared usages contract: source, resource, resource_id, role, label.
 *
 * Source scanned: `cms_file_references`, the Domain-owned canonical registry
 * maintained transactionally by each media-bearing service and by bootstrap.
 *
 * @phpstan-type UsageItem array{source: string, resource: string, resource_id: int, role: string, label: string|null, context?: array{owner_type: string, owner_id: int, file_id: int, block_key: string, block_name: string}}
 */
class FileUsageService
{
    /** @var BaseConnection<mixed, mixed> */
    private BaseConnection $db;

    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * @return list<UsageItem>
     */
    public function getUsagesByHubFileId(int $hubFileId): array
    {
        if (! $this->db->tableExists('cms_file_references')) {
            throw new \RuntimeException(lang('Cms.file_references.missing_table'));
        }

        $result = $this->db->table('cms_file_references fr')
            ->select('fr.resource_type, fr.resource_id, fr.role, fr.label, bi.owner_type, bi.owner_id, bt.block_key, bt.name as block_name')
            ->join('cms_block_instances bi', 'bi.id = fr.block_instance_id', 'left')
            ->join('cms_content_blocks bt', 'bt.id = bi.block_id', 'left')
            ->where('fr.hub_file_id', $hubFileId)
            ->orderBy('fr.resource_type', 'ASC')
            ->orderBy('fr.resource_id', 'ASC')
            ->orderBy('fr.role', 'ASC')
            ->get();
        $rows = $result ? $result->getResultArray() : [];

        return array_values(array_map(static function (array $row) use ($hubFileId): array {
            $resourceType = (string) ($row['resource_type'] ?? '');
            $usage = [
                'source'      => 'domain',
                'resource'    => match ($resourceType) {
                    'entry' => 'entries',
                    'page' => 'pages',
                    'setting' => 'settings',
                    'block_instance' => 'block_instances',
                    default => $resourceType,
                },
                'resource_id' => (int) ($row['resource_id'] ?? 0),
                'role'        => (string) ($row['role'] ?? 'default'),
                'label'       => isset($row['label']) && trim((string) $row['label']) !== ''
                    ? (string) $row['label']
                    : null,
            ];

            if ($resourceType === 'block_instance') {
                $usage['context'] = [
                    'owner_type' => (string) ($row['owner_type'] ?? ''),
                    'owner_id'   => (int) ($row['owner_id'] ?? 0),
                    'file_id'    => $hubFileId,
                    'block_key'  => (string) ($row['block_key'] ?? ''),
                    'block_name' => (string) ($row['block_name'] ?? ''),
                ];
            }

            return $usage;
        }, $rows));
    }
}
