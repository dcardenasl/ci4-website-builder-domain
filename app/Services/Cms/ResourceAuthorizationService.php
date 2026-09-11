<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Interfaces\Cms\ResourceAuthorizationInterface;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\ResultInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Services\AuditServiceInterface;

/**
 * Domain-owned resource scope.
 *
 * The Hub remains the source of identity and global permissions. This service
 * only evaluates the concrete CMS scope and deliberately does not cache it:
 * permission changes are effective on the next request that reaches Domain.
 */
final class ResourceAuthorizationService implements ResourceAuthorizationInterface
{
    private const ACCESS_RANK = ['read' => 1, 'write' => 2, 'admin' => 3];

    /** @var array<string, string> */
    private const RESOURCE_TABLES = [
        'page' => 'cms_pages',
        'entry' => 'cms_entries',
        'collection' => 'cms_collections',
    ];

    /**
     * @param BaseConnection<mixed, mixed> $db
     * @param AuditServiceInterface|null $audit
     */
    public function __construct(
        private readonly BaseConnection $db,
        private readonly ?AuditServiceInterface $audit = null,
    ) {
    }

    public function assertCan(string $resourceType, int $resourceId, string $action, ?SecurityContext $context): void
    {
        $resourceType = $this->normalizeType($resourceType);
        $requiredRank = self::ACCESS_RANK[$this->normalizeAction($action)] ?? throw new \InvalidArgumentException(lang('Api.unsupportedResourceAction'));

        if ($resourceId < 1 || ! $this->resourceExists($resourceType, $resourceId)) {
            $this->auditDenied($resourceType, $resourceId, $action, $context);
            throw new NotFoundException(lang('Api.resourceNotFound'));
        }

        if ($context?->user_id !== null && $this->isSuperAdmin($context)) {
            return;
        }

        if ($context?->user_id !== null && $this->effectiveRank($resourceType, $resourceId, $context->user_id) >= $requiredRank) {
            return;
        }

        $this->auditDenied($resourceType, $resourceId, $action, $context);

        // A scoped resource must not be enumerable. Callers receive the same
        // 404 for a missing resource and for a resource outside their scope.
        throw new NotFoundException(lang('Api.resourceNotFound'));
    }

    public function scopeCriteria(string $resourceType, array $criteria, ?SecurityContext $context): array
    {
        if ($context?->user_id === null || $this->isSuperAdmin($context)) {
            return $criteria;
        }

        $ids = $this->scopedIds($this->normalizeType($resourceType), $context->user_id);
        $existing = $criteria['filter']['id'] ?? null;
        if ($existing !== null && ! is_array($existing)) {
            $ids = in_array((int) $existing, $ids, true) ? [(int) $existing] : [];
        } elseif (is_array($existing)) {
            $requested = $existing['in'] ?? $existing;
            if (is_array($requested)) {
                $ids = array_values(array_intersect($ids, array_map('intval', $requested)));
            }
        }

        $criteria['scope_ids'] = $ids;

        return $criteria;
    }

    public function projectionCriteria(string $resourceType, array $criteria, ?SecurityContext $context): array
    {
        if ($context?->user_id === null) {
            $criteria['resource_scope_user_id'] = 0;
            $criteria['resource_scope_superadmin'] = false;
        } elseif ($this->isSuperAdmin($context)) {
            $criteria['resource_scope_superadmin'] = true;
        } else {
            $criteria['resource_scope_user_id'] = $context->user_id;
            $criteria['resource_scope_superadmin'] = false;
        }

        return $criteria;
    }

    public function grantOnCreate(string $resourceType, int $resourceId, ?SecurityContext $context): void
    {
        if ($context?->user_id === null) {
            return;
        }

        $this->upsertGrant($this->normalizeType($resourceType), $resourceId, $context->user_id, 'admin', $context->user_id);
    }

    public function listGrants(string $resourceType, int $resourceId, ?SecurityContext $context): array
    {
        $resourceType = $this->normalizeType($resourceType);
        $this->assertCanManage($resourceType, $resourceId, $context);

        $rows = $this->resultRows($this->db->table('cms_resource_access')
            ->select('user_id, access_level')
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->orderBy('user_id', 'ASC')
            ->get());

        return array_values(array_map(static fn (array $row): array => [
            'user_id' => (int) $row['user_id'],
            'access_level' => (string) $row['access_level'],
        ], $rows));
    }

    public function grant(string $resourceType, int $resourceId, int $userId, string $accessLevel, SecurityContext $context): void
    {
        $resourceType = $this->normalizeType($resourceType);
        $accessLevel = $this->normalizeAction($accessLevel);
        if ($userId < 1 || ! isset(self::ACCESS_RANK[$accessLevel])) {
            throw new \InvalidArgumentException(lang('Api.invalidResourceGrant'));
        }

        $this->assertCanManage($resourceType, $resourceId, $context);
        if ($accessLevel === 'admin' && ! $this->isSuperAdmin($context)) {
            throw new AuthorizationException(lang('Api.forbidden'));
        }

        $this->upsertGrant($resourceType, $resourceId, $userId, $accessLevel, $context->user_id);
    }

    public function revoke(string $resourceType, int $resourceId, int $userId, SecurityContext $context): void
    {
        $resourceType = $this->normalizeType($resourceType);
        $this->assertCanManage($resourceType, $resourceId, $context);
        if ($userId < 1) {
            throw new \InvalidArgumentException(lang('Api.invalidResourceUser'));
        }

        $table = $this->db->table('cms_resource_access');
        $target = $this->resultRow($table
            ->select('access_level')
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('user_id', $userId)
            ->get());
        if (($target['access_level'] ?? null) === 'admin') {
            $adminCount = $this->db->table('cms_resource_access')
                ->where('resource_type', $resourceType)
                ->where('resource_id', $resourceId)
                ->where('access_level', 'admin')
                ->countAllResults();
            if ($adminCount <= 1) {
                throw new ValidationException(lang('Api.invalidRequest'), [
                    'user_id' => lang('Api.resourceMustRetainAdministrator'),
                ]);
            }
        }

        $table
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function transferOwnership(string $resourceType, int $resourceId, int $userId, SecurityContext $context): void
    {
        $resourceType = $this->normalizeType($resourceType);
        $this->assertCanManage($resourceType, $resourceId, $context);
        if ($userId < 1) {
            throw new \InvalidArgumentException(lang('Api.invalidResourceOwner'));
        }

        $this->db->transStart();
        $this->db->table('cms_resource_access')
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('access_level', 'admin')
            ->update(['access_level' => 'write']);
        $this->upsertGrant($resourceType, $resourceId, $userId, 'admin', $context->user_id);
        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new \RuntimeException(lang('Api.transactionFailed'));
        }
    }

    private function assertCanManage(string $resourceType, int $resourceId, ?SecurityContext $context): void
    {
        $this->assertCan($resourceType, $resourceId, 'admin', $context);
    }

    private function effectiveRank(string $resourceType, int $resourceId, int $userId): int
    {
        $directLevels = $this->resultRows($this->db->table('cms_resource_access')
            ->select('access_level')
            ->where('user_id', $userId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->get());

        $levels = $directLevels;
        $collectionIds = $this->collectionIdsFor($resourceType, $resourceId);
        if ($collectionIds !== []) {
            $levels = array_merge($levels, $this->resultRows($this->db->table('cms_resource_access')
                ->select('access_level')
                ->where('user_id', $userId)
                ->where('resource_type', 'collection')
                ->whereIn('resource_id', $collectionIds)
                ->get()));
        }

        $rank = 0;
        foreach ($levels as $level) {
            $rank = max($rank, self::ACCESS_RANK[(string) ($level['access_level'] ?? '')] ?? 0);
        }

        return $rank;
    }

    /** @return list<int> */
    private function scopedIds(string $resourceType, int $userId): array
    {
        $builder = $this->db->table('cms_resource_access')
            ->select('resource_id')
            ->where('resource_type', $resourceType)
            ->where('user_id', $userId);
        $ids = array_map('intval', array_column($this->resultRows($builder->get()), 'resource_id'));

        if (in_array($resourceType, ['page', 'entry'], true)) {
            $table = $resourceType === 'page' ? 'cms_pages' : 'cms_entries';
            $rows = $this->resultRows($this->db->table($table . ' r')
                ->select('r.id')
                ->join('cms_resource_access ra', "ra.resource_type = 'collection' AND ra.resource_id = r.collection_id", 'inner', false)
                ->where('ra.user_id', $userId)
                ->get());
            $ids = array_values(array_unique([...$ids, ...array_map('intval', array_column($rows, 'id'))]));
        }

        sort($ids);

        return $ids;
    }

    /** @return list<int> */
    private function collectionIdsFor(string $resourceType, int $resourceId): array
    {
        if (! in_array($resourceType, ['page', 'entry'], true)) {
            return [];
        }

        $table = $resourceType === 'page' ? 'cms_pages' : 'cms_entries';
        $row = $this->resultRow($this->db->table($table)->select('collection_id')->where('id', $resourceId)->get());
        $collectionId = is_array($row) && isset($row['collection_id']) ? (int) $row['collection_id'] : 0;

        return $collectionId > 0 ? [$collectionId] : [];
    }

    private function resourceExists(string $resourceType, int $resourceId): bool
    {
        $table = self::RESOURCE_TABLES[$resourceType];
        $builder = $this->db->table($table)->select('id')->where('id', $resourceId);
        if (in_array($resourceType, ['page', 'entry'], true)) {
            $builder->where('deleted_at', null);
        }

        return $this->resultRow($builder->get()) !== null;
    }

    private function upsertGrant(string $resourceType, int $resourceId, int $userId, string $accessLevel, ?int $createdBy): void
    {
        $builder = $this->db->table('cms_resource_access');
        $existing = $this->resultRow($builder
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('user_id', $userId)
            ->get());

        if ($existing !== null) {
            $builder->where('id', (int) $existing['id'])->update([
                'access_level' => $accessLevel,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return;
        }

        $builder->insert([
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'user_id' => $userId,
            'access_level' => $accessLevel,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function isSuperAdmin(SecurityContext $context): bool
    {
        return $context->hasPermission('iam.superadmin-access');
    }

    private function normalizeType(string $resourceType): string
    {
        $resourceType = strtolower(trim($resourceType));
        if (! in_array($resourceType, ['page', 'entry', 'collection'], true)) {
            throw new \InvalidArgumentException(lang('Api.unsupportedResourceType'));
        }

        return $resourceType;
    }

    private function normalizeAction(string $action): string
    {
        $action = strtolower(trim($action));
        if (! isset(self::ACCESS_RANK[$action])) {
            throw new \InvalidArgumentException(lang('Api.unsupportedResourceAction'));
        }

        return $action;
    }

    private function auditDenied(string $resourceType, int $resourceId, string $action, ?SecurityContext $context): void
    {
        try {
            $this->audit?->log(
                'authorization_denied_resource',
                $resourceType,
                $resourceId,
                [],
                [],
                $context,
                'denied',
                'warning',
                ['required_access' => $action],
            );
        } catch (\Throwable) {
            // Authorization must never become unavailable because its audit
            // transport is unavailable.
        }
    }

    /**
     * @param ResultInterface<mixed, mixed>|false $result
     * @return array<int, array<string, mixed>>
     */
    private function resultRows(ResultInterface|false $result): array
    {
        if (! $result instanceof ResultInterface) {
            return [];
        }

        return $result->getResultArray();
    }

    /**
     * @param ResultInterface<mixed, mixed>|false $result
     * @return array<string, mixed>|null
     */
    private function resultRow(ResultInterface|false $result): ?array
    {
        if (! $result instanceof ResultInterface) {
            return null;
        }

        return $result->getRowArray() ?: null;
    }
}
