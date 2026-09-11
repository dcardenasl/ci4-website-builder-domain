<?php

declare(strict_types=1);

namespace App\Services\Editor;

use App\DTO\Editor\EditorDocumentPatchRequestDTO;
use App\DTO\Editor\EditorDocumentPatchResponseDTO;
use App\DTO\Editor\EditorOwnerDTO;
use App\Interfaces\Cms\ResourceAuthorizationInterface;
use App\Interfaces\Editor\EditorDocumentPatchServiceInterface;
use App\Libraries\Cms\EditorCacheInvalidationClient;
use App\Libraries\Cms\EditorDocumentAssembler;
use App\Libraries\Cms\EditorDocumentSnapshotReader;
use App\Libraries\Cms\EditorPatchPlanner;
use App\Libraries\Cms\EditorPatchWriter;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Editor;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Exceptions\ServiceUnavailableException;
use dcardenasl\Ci4ApiCore\Support\OperationResult;

final class EditorDocumentPatchService implements EditorDocumentPatchServiceInterface
{
    /** @param BaseConnection<mixed, mixed> $db */
    public function __construct(
        private readonly BaseConnection $db,
        private readonly EditorDocumentSnapshotReader $snapshots,
        private readonly EditorDocumentAssembler $assembler,
        private readonly EditorPatchPlanner $planner,
        private readonly EditorPatchWriter $writer,
        private readonly EditorCacheInvalidationClient $cache,
        private readonly Editor $limits,
        private readonly ?ResourceAuthorizationInterface $resourceAuthorization = null,
    ) {
    }

    public function maxPayloadBytes(): int
    {
        return $this->limits->maxPayloadBytes;
    }

    /** @param array<string, mixed> $payload */
    public function patch(EditorOwnerDTO $owner, array $payload, ?SecurityContext $context): OperationResult
    {
        if ($context?->user_id === null || ! $context->hasPermission($owner->permission())) {
            throw new AuthorizationException(lang('Api.insufficientPermissions'));
        }
        $this->resourceAuthorization?->assertCan($owner->type, $owner->id, 'write', $context);
        $patch = EditorDocumentPatchRequestDTO::fromArray($payload, $this->limits);
        if ($this->db->transDepth !== 0) {
            throw new \LogicException(lang('Editor.patchNested'));
        }
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        }
        if (! $this->db->transBegin()) {
            throw new ServiceUnavailableException(lang('Editor.busy'));
        }
        $this->cache->discard();
        try {
            $snapshot = $this->snapshots->read($owner, true);
            $document = $this->assembler->assemble($owner, $snapshot);
            if (! hash_equals($snapshot->version(), $patch->baseVersion)) {
                $this->db->transRollback();

                return OperationResult::success(new EditorDocumentPatchResponseDTO($document, true), lang('Editor.conflict'), 409);
            }
            $nodes = $this->planner->plan($document, $patch);
            $idMap = $this->writer->apply($owner, $snapshot, $nodes, $context);
            $fresh = $this->assembler->assemble($owner, $this->snapshots->read($owner, true));
            if (! $this->db->transStatus() || ! $this->db->transCommit()) {
                throw new ServiceUnavailableException(lang('Editor.busy'));
            }
        } catch (\Throwable $exception) {
            $this->db->transRollback();
            $this->cache->discard();
            if ($exception instanceof DatabaseException && preg_match('/deadlock|lock wait|database is locked/i', $exception->getMessage()) === 1) {
                throw new ServiceUnavailableException(lang('Editor.busy'));
            }
            throw $exception;
        }
        // Cache is outside the DB transaction. If this fails the client must
        // reconcile via GET, just as after a lost response; never claim rollback.
        $this->cache->flush();

        return OperationResult::success(new EditorDocumentPatchResponseDTO($fresh, false, $idMap));
    }
}
