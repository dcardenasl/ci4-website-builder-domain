<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Seeds editable integration settings required by the demo site.
 */
final class SiteIntegrationSettingsSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        foreach ($this->settings() as $setting) {
            $key = (string) $setting['setting_key'];
            unset($setting['setting_key']);

            $this->upsertRecord('cms_settings', ['setting_key' => $key], $setting);
        }
    }

    /** @return list<array<string, int|string>> */
    private function settings(): array
    {
        return [
            [
                'setting_key' => 'recaptcha_site_key',
                'setting_value' => '',
                'setting_type' => 'string',
                'input_type' => 'text',
                'setting_group' => 'integration',
                'is_translatable' => 0,
                'is_required' => 0,
                'is_readonly' => 0,
                'is_public' => 1,
                'is_active' => 1,
                'sort_order' => 900,
                'description' => 'Clave pública de reCAPTCHA usada por el sitio web.',
            ],
            [
                'setting_key' => 'recaptcha_secret_key',
                'setting_value' => '',
                'setting_type' => 'string',
                'input_type' => 'text',
                'setting_group' => 'integration',
                'is_translatable' => 0,
                'is_required' => 0,
                'is_readonly' => 0,
                'is_public' => 0,
                'is_active' => 1,
                'sort_order' => 901,
                'description' => 'Clave secreta de reCAPTCHA usada para validar envíos.',
            ],
        ];
    }
}
