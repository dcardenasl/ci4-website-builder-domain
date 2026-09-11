<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\SortOrderBatchRequestDTO;
use App\Interfaces\Cms\ResourceAuthorizationInterface;
use App\Libraries\Cms\CacheInvalidationClient;
use CodeIgniter\Database\BaseConnection;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use RuntimeException;

/** Atomic batch ordering for generic CMS resources. */
final class SortOrderService
{
    /** @var array<string, array{table: string, cache: list<string>}> */
    private const RESOURCES = [
        'collections' => ['table' => 'cms_collections', 'cache' => ['collections', 'entries']],
        'pages' => ['table' => 'cms_pages', 'cache' => ['pages']],
        'entries' => ['table' => 'cms_entries', 'cache' => ['entries']],
        'categories' => ['table' => 'cms_categories', 'cache' => ['categories']],
        'languages' => ['table' => 'cms_languages', 'cache' => ['languages']],
        'menu_items' => ['table' => 'cms_menu_items', 'cache' => ['menus']],
        'block_instances' => ['table' => 'cms_block_instances', 'cache' => ['pages', 'entries']],
    ];

    /** @param BaseConnection<mixed, mixed> $database */
    public function __construct(
        private readonly BaseConnection $database,
        private readonly CacheInvalidationClient $cacheInvalidator,
        private readonly ?ResourceAuthorizationInterface $resourceAuthorization = null,
    ) {
    }

    /** @return array{updated: int} */
    public function reorder(SortOrderBatchRequestDTO $request, ?SecurityContext $context = null): array
    {
        $configuration = self::RESOURCES[$request->resource] ?? null;
        if ($configuration === null) {
            throw new ValidationException(lang('Api.invalidRequest'));
        }

        $this->authorizeScope($request, $context);

        $scope = $this->validatedScope($request->resource, $request->scope);
        $ids = array_map(static fn (array $item): int => $item['id'], $request->items);
        $this->database->transStart();

        $existingBuilder = $this->database->table($configuration['table'])
            ->select('id')
            ->whereIn('id', $ids);
        $this->applyScope($existingBuilder, $request->resource, $scope);
        $existingResult = $existingBuilder->get();
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
        [$scopeSql, $scopeBindings] = $this->scopeSql($request->resource, $scope);
        $sql .= $scopeSql;
        $bindings = [...$bindings, ...$scopeBindings];

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

    private function authorizeScope(SortOrderBatchRequestDTO $request, ?SecurityContext $context): void
    {
        if ($context === null || $this->resourceAuthorization === null) {
            return;
        }

        $resourceType = match ($request->resource) {
            'pages' => 'page',
            'entries' => 'entry',
            'collections' => 'collection',
            default => null,
        };
        if ($resourceType !== null) {
            foreach ($request->items as $item) {
                $this->resourceAuthorization->assertCan($resourceType, $item['id'], 'write', $context);
            }

            return;
        }

        if ($request->resource === 'block_instances') {
            $ownerType = (string) ($request->scope['owner_type'] ?? '');
            $ownerId = (int) ($request->scope['owner_id'] ?? 0);
            $this->resourceAuthorization->assertCan($ownerType, $ownerId, 'write', $context);
        }
    }

    /**
     * @param array<string, int|string|null> $scope
     * @return array<string, int|string|null>
     */
    private function validatedScope(string $resource, array $scope): array
    {
        if ($resource === 'entries' && (! isset($scope['collection_id']) || ! is_int($scope['collection_id']))) {
            throw new ValidationException(lang('Api.invalidRequest'));
        }
        if ($resource === 'menu_items' && (! isset($scope['menu_id']) || ! is_int($scope['menu_id']))) {
            throw new ValidationException(lang('Api.invalidRequest'));
        }
        if ($resource === 'block_instances') {
            if (! isset($scope['owner_type'], $scope['owner_id']) || ! is_string($scope['owner_type']) || ! is_int($scope['owner_id'])) {
                throw new ValidationException(lang('Api.invalidRequest'));
            }
        }

        return $scope;
    }

    /**
     * @param \CodeIgniter\Database\BaseBuilder $builder
     * @param array<string, int|string|null> $scope
     */
    private function applyScope(\CodeIgniter\Database\BaseBuilder $builder, string $resource, array $scope): void
    {
        foreach ($this->scopeConditions($resource, $scope) as [$column, $value]) {
            $builder->where($column, $value);
        }
    }

    /**
     * @param array<string, int|string|null> $scope
     * @return list<array{0: string, 1: int|string|null}>
     */
    private function scopeConditions(string $resource, array $scope): array
    {
        return match ($resource) {
            'entries' => [['collection_id', $scope['collection_id']]],
            'menu_items' => [['menu_id', $scope['menu_id']]],
            'block_instances' => [
                ['owner_type', $scope['owner_type']],
                ['owner_id', $scope['owner_id']],
                ...array_key_exists('parent_instance_id', $scope)
                    ? [['parent_instance_id', $scope['parent_instance_id']]]
                    : [],
            ],
            default => [],
        };
    }

    /**
     * @param array<string, int|string|null> $scope
     * @return array{0: string, 1: list<int|string|null>}
     */
    private function scopeSql(string $resource, array $scope): array
    {
        $sql = '';
        $bindings = [];
        foreach ($this->scopeConditions($resource, $scope) as [$column, $value]) {
            if ($value === null) {
                $sql .= ' AND `' . $column . '` IS NULL';
                continue;
            }
            $sql .= ' AND `' . $column . '` = ?';
            $bindings[] = $value;
        }

        return [$sql, $bindings];
    }
}
