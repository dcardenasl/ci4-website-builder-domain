<?php

declare(strict_types=1);

namespace Config;

/**
 * System Monitoring Services
 *
 * Audit logging infrastructure used by BaseAuditableModel and the
 * Auditable trait so that domain CRUDs persist a local audit log.
 */
trait SystemMonitoringServices
{
    public static function auditService(bool $getShared = true): \dcardenasl\Ci4ApiCore\Services\AuditServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('auditService');
        }

        $queueManager = static::auditQueueManager();

        // The sync transport executes jobs inline but is not a processing
        // QueueManager. AuditService uses null to persist audit entries
        // synchronously when no asynchronous queue is available.
        $auditQueueManager = $queueManager instanceof \dcardenasl\Ci4ApiCore\Queue\QueueManager
            ? $queueManager
            : null;

        return new \dcardenasl\Ci4ApiCore\Services\Audit\AuditService(
            static::auditRepository(),
            static::auditResponseMapper(),
            static::auditWriter(),
            $auditQueueManager,
            config('Audit'),
            ENVIRONMENT !== 'testing',
            '127.0.0.1',
            'system',
            static::auditPayloadSanitizer()
        );
    }

    public static function auditWriter(bool $getShared = true): \dcardenasl\Ci4ApiCore\Services\Audit\AuditWriter
    {
        if ($getShared) {
            return static::getSharedInstance('auditWriter');
        }

        return new \dcardenasl\Ci4ApiCore\Services\Audit\AuditWriter(
            static::auditRepository()
        );
    }

    public static function auditPayloadSanitizer(bool $getShared = true): \dcardenasl\Ci4ApiCore\Services\Audit\AuditPayloadSanitizer
    {
        if ($getShared) {
            return static::getSharedInstance('auditPayloadSanitizer');
        }

        return new \dcardenasl\Ci4ApiCore\Services\Audit\AuditPayloadSanitizer();
    }

    public static function requestAuditContextFactory(bool $getShared = true): \dcardenasl\Ci4ApiCore\Support\RequestAuditContextFactory
    {
        if ($getShared) {
            return static::getSharedInstance('requestAuditContextFactory');
        }

        return new \dcardenasl\Ci4ApiCore\Support\RequestAuditContextFactory();
    }

    public static function auditResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('auditResponseMapper');
        }

        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(
            \App\DTO\Response\Audit\AuditResponseDTO::class
        );
    }
}
