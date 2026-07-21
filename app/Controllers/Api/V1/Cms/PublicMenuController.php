<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Interfaces\Cms\MenuServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicMenuController extends ApiController
{
    protected MenuServiceInterface $menuService;

    protected function resolveDefaultService(): MenuServiceInterface
    {
        $this->menuService = Services::menuService();

        return $this->menuService;
    }

    /**
     * Resolve a public menu tree by its menu_key and language.
     *
     * @param string $menuKey Unique menu identifier
     */
    public function show(string $menuKey): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($menuKey): ResponseInterface {
                // The API's static framework locale list must not decide CMS
                // content language. The public web client sends the locale in
                // Accept-Language after discovering it from the CMS.
                $lang = Services::publicLocaleResolver()->resolve($this->request->getHeaderLine('Accept-Language'));

                $data = $this->menuService->showPublic($menuKey, $lang);

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $data,
                ])->setStatusCode(200);
            }
        );
    }
}
