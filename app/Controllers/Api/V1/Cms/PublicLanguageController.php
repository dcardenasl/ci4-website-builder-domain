<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Interfaces\Cms\LanguageServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

final class PublicLanguageController extends ApiController
{
    protected LanguageServiceInterface $languageService;

    protected function resolveDefaultService(): LanguageServiceInterface
    {
        $this->languageService = Services::languageService();

        return $this->languageService;
    }

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context): mixed {
                return $this->languageService->listPublic();
            }
        );
    }
}
