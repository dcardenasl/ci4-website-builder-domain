<?php

declare(strict_types=1);

namespace App\Services\Editor;

use App\DTO\Editor\EditorDocumentResponseDTO;
use App\DTO\Editor\EditorOwnerDTO;
use App\Interfaces\Editor\EditorDocumentReaderInterface;
use App\Libraries\Cms\EditorDocumentAssembler;
use App\Libraries\Cms\EditorDocumentSnapshotReader;
use CodeIgniter\Database\BaseConnection;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;

final class EditorDocumentReader implements EditorDocumentReaderInterface
{
    /** @param BaseConnection<mixed, mixed> $db */
    public function __construct(
        private readonly BaseConnection $db,
        private readonly EditorDocumentSnapshotReader $snapshots,
        private readonly EditorDocumentAssembler $assembler,
    ) {
    }

    public function load(EditorOwnerDTO $owner, ?SecurityContext $context): EditorDocumentResponseDTO
    {
        if ($context?->user_id === null || ! $context->hasPermission($owner->permission())) {
            throw new AuthorizationException(lang('Api.insufficientPermissions'));
        }
        // This public read entry point owns its transaction, ensuring every table
        // contributes to the same snapshot even if the server defaults to READ COMMITTED.
        if ($this->db->transDepth !== 0) {
            throw new \LogicException(lang('Editor.snapshotNested'));
        }
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        }
        if (! $this->db->transBegin()) {
            throw new \RuntimeException(lang('Editor.snapshotUnavailable'));
        }
        try {
            $document = $this->assembler->assemble($owner, $this->snapshots->read($owner));
            if (! $this->db->transStatus() || ! $this->db->transCommit()) {
                throw new \RuntimeException(lang('Editor.snapshotIncomplete'));
            }

            return $document;
        } catch (\Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }
}
