<?php

declare(strict_types=1);

namespace App\Repositories\Cms;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;
use dcardenasl\Ci4ApiCore\Repositories\BaseRepository;

/**
 * Shared execution seam for CMS administrative SQL read models.
 *
 * Resource repositories own their SQL and allowlists; this class owns only
 * pagination normalization and the common result envelope.
 *
 * @extends BaseRepository<object>
 */
abstract class AbstractAdminListProjectionRepository extends BaseRepository
{
    /**
     * @param Model $model
     * @param BaseConnection<mixed, mixed> $db
     */
    public function __construct(
        Model $model,
        protected readonly BaseConnection $db,
    ) {
        parent::__construct($model);
    }

    /**
     * Read a table filter from either the canonical nested shape or the
     * flattened shape emitted by the Admin table client.
     *
     * @param array<string, mixed> $criteria
     */
    protected function criteriaValue(array $criteria, string $key): mixed
    {
        $filter = $criteria['filter'] ?? null;
        if (is_array($filter) && array_key_exists($key, $filter)) {
            return $filter[$key];
        }

        return $criteria[$key] ?? null;
    }

    /**
     * @param array<int|string, mixed> $binds
     * @return array{
     *     data: list<array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     last_page: int,
     *     from: int,
     *     to: int
     * }
     */
    protected function executeProjection(
        string $sql,
        array $binds,
        int $page,
        int $perPage,
        string $errorMessage,
    ): array {
        $page = max(1, $page);
        $perPage = min(1000, max(1, $perPage));
        $offset = ($page - 1) * $perPage;

        $this->configureProjectionTransport();
        $query = $this->db->query($sql, $binds);
        if (! $query instanceof ResultInterface) {
            throw new \RuntimeException($errorMessage);
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $query->getResultArray();
        $total = $rows !== [] ? (int) ($rows[0]['total_items'] ?? 0) : 0;
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 0;
        $from = $rows !== [] ? $offset + 1 : 0;
        $to = $rows !== [] ? $offset + count($rows) : 0;

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'from' => $from,
            'to' => $to,
        ];
    }

    private function configureProjectionTransport(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        // GROUP_CONCAT is the compatibility aggregate used by the list
        // projections on MySQL/MariaDB versions without JSON_ARRAYAGG.
        $this->db->query('SET SESSION group_concat_max_len = 1048576');
    }
}
