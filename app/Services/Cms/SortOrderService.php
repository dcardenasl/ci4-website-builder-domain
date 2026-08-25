<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\SortOrderBatchRequestDTO;
use App\Libraries\Cms\CacheInvalidationClient;
use CodeIgniter\Database\BaseConnection;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use RuntimeException;

/** Atomic batch ordering for generic CMS resources. */
final class SortOrderService
{
    /** @var array<string, array{table: string, cache: list<string>}> */
    private const RESOURCES = [
        'collections' => ['table' => 'cms_collections', 'cache' => ['collections', 'entries']],
    ];

    /** @param BaseConnection<mixed, mixed> $database */
    public function __construct(
        private readonly BaseConnection $database,
        private readonly CacheInvalidationClient $cacheInvalidator,
    ) {
    }

    /** @return array{updated: int} */
    public function reorder(SortOrderBatchRequestDTO $request): array
    {
        $configuration = self::RESOURCES[$request->resource] ?? null;
        if ($configuration === null) {
            throw new ValidationException(lang('Api.invalidRequest'));
        }

        $ids = array_map(static fn (array $item): int => $item['id'], $request->items);
        $this->database->transStart();

        $existingResult = $this->database->table($configuration['table'])
            ->select('id')
            ->whereIn('id', $ids)
            ->get();
        if ($existingResult === false || $existingResult->getNumRows() !== count($ids)) {
            $this->database->transRollback();
            throw new ValidationException(lang('Api.invalidRequest'));
        }

        $caseFragments = [];
        $bindings = [];
        foreach ($request->items as $item) {
            $caseFragments[] = 'WHEN ? THEN ?';
            $bindings[] = $item['id'];
            $bindings[] = $item['sort_order'];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = sprintf(
            'UPDATE `%s` SET `sort_order` = CASE `id` %s ELSE `sort_order` END WHERE `id` IN (%s)',
            $configuration['table'],
            implode(' ', $caseFragments),
            $placeholders,
        );
        $bindings = [...$bindings, ...$ids];

        if (! $this->database->query($sql, $bindings)) {
            $this->database->transRollback();
            throw new RuntimeException(lang('Api.transactionFailed'));
        }

        $this->database->transComplete();
        if ($this->database->transStatus() === false) {
            throw new RuntimeException(lang('Api.transactionFailed'));
        }

        $this->cacheInvalidator->invalidate($configuration['cache']);

        return ['updated' => count($ids)];
    }
}
