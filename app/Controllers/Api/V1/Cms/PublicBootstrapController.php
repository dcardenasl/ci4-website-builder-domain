<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Libraries\Cms\PreviewToken;
use App\Services\Cms\PublicBootstrapService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

final class PublicBootstrapController extends ApiController
{
    private PublicBootstrapService $bootstrapService;

    protected function resolveDefaultService(): PublicBootstrapService
    {
        $this->bootstrapService = Services::publicBootstrapService();

        return $this->bootstrapService;
    }

    public function layout(): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context): ResponseInterface {
                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $this->bootstrapService->layout($this->request->getHeaderLine('Accept-Language')),
                ]);
            }
        );
    }

    public function pageBootstrap(string $path): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($path): ResponseInterface {
                $lang = Services::publicLocaleResolver()->resolve($this->request->getHeaderLine('Accept-Language'));
                $previewExpiresRaw = $this->request->getGet('preview_expires');
                $previewSigRaw = $this->request->getGet('preview_sig');
                $preview = $this->request->getGet('preview') === '1'
                    && PreviewToken::verify(
                        'page',
                        $lang . ':' . trim($path, '/'),
                        is_string($previewExpiresRaw) ? $previewExpiresRaw : null,
                        is_string($previewSigRaw) ? $previewSigRaw : null
                    );

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $this->bootstrapService->pageBootstrap(
                        $path,
                        $this->request->getHeaderLine('Accept-Language'),
                        $preview,
                    ),
                ]);
            }
        );
    }
}
