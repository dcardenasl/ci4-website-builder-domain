<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\FileTranslationEntity;
use App\Interfaces\Cms\FileTranslationServiceInterface;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<FileTranslationEntity>
 */
class FileTranslationService extends BaseCrudService implements FileTranslationServiceInterface
{
    /**
     * @param RepositoryInterface<FileTranslationEntity> $fileTranslationRepository
     */
    public function __construct(
        RepositoryInterface $fileTranslationRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($fileTranslationRepository, $responseMapper);
    }
}
