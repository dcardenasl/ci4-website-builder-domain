<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicSettingController extends ApiController
{
    protected function resolveDefaultService(): object
    {
        return Services::settingService();
    }

    /**
     * Get all public settings with their translations.
     * Settings marked as is_public=1 are returned with translation values.
     */
    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (): ResponseInterface {
                $lang = $this->request->getLocale();

                // Get all active, public settings
                $settingModel = model(\App\Models\SettingModel::class);
                $settings = $settingModel->where('is_public', 1)
                    ->where('is_active', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();

                $translationResolver = Services::translationResolver();
                $result = [];

                foreach ($settings as $setting) {
                    if ($setting instanceof \App\Entities\SettingEntity) {
                        // If translatable, get translation; otherwise use setting_value
                        if ($setting->is_translatable) {
                            $translation = $translationResolver->resolve('setting', (int) $setting->id, $lang);
                            $value = $translation['setting_value'] ?? $setting->setting_value;
                        } else {
                            $value = $setting->setting_value;
                        }

                        $result[$setting->setting_key] = $value;
                    }
                }

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $result,
                ])->setStatusCode(200);
            }
        );
    }
}
