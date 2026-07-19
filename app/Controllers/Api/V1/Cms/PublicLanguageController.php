<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

final class PublicLanguageController extends ApiController
{
    protected function resolveDefaultService(): object
    {
        return Services::languageService();
    }

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (): ResponseInterface {
                $languages = model(\App\Models\LanguageModel::class)
                    ->where('is_active', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('code', 'ASC')
                    ->findAll();

                return $this->response->setJSON([
                    'status' => 'success',
                    'data' => array_map(static fn ($language): array => [
                        'code' => (string) $language->code,
                        'name' => (string) $language->name,
                        'native_name' => (string) $language->native_name,
                        'is_default' => (bool) $language->is_default,
                    ], $languages),
                ]);
            }
        );
    }
}
