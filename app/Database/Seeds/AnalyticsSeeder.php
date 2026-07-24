<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $settings = [
            [
                'setting_key'     => 'analytics_provider',
                'setting_value'   => 'none',
                'setting_type'    => 'string',
                'setting_group'   => 'analytics',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 10,
                'description'     => 'Proveedor de analytics: none | ga4 | plausible | fathom',
            ],
            [
                'setting_key'     => 'analytics_id',
                'setting_value'   => '',
                'setting_type'    => 'string',
                'setting_group'   => 'analytics',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 20,
                'description'     => 'ID de seguimiento (GA4: G-XXXX, Plausible: dominio, Fathom: código de sitio)',
            ],
        ];

        foreach ($settings as $setting) {
            $this->upsertRecord('cms_settings', [
                'setting_key' => $setting['setting_key'],
            ], [
                'setting_value'   => $setting['setting_value'],
                'setting_type'    => $setting['setting_type'],
                'setting_group'   => $setting['setting_group'],
                'is_translatable' => $setting['is_translatable'],
                'is_public'       => $setting['is_public'],
                'is_active'       => $setting['is_active'],
                'sort_order'      => $setting['sort_order'],
                'description'     => $setting['description'],
            ]);
        }
    }
}
