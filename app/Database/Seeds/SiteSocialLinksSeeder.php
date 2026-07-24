<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Seeds global social media links for footer display.
 *
 * Social media URLs can be configured in 3 ways (in order of preference):
 * 1. Admin UI: Edit settings in the CMS admin panel
 * 2. .env file: Set in Web/.env, then run: php spark social:sync
 * 3. Database: Edit cms_settings table directly
 *
 * All URLs support validation — empty/placeholder values are hidden from footer.
 * Current networks: Facebook, Instagram, Twitter, LinkedIn, YouTube, TikTok, Pinterest, GitHub.
 */
class SiteSocialLinksSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $settings = [
            [
                'setting_key'     => 'social_facebook',
                'setting_value'   => 'https://www.facebook.com/',
                'setting_type'    => 'string',
                'input_type'      => 'url',
                'setting_group'   => 'social',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 10,
                'description'     => 'URL del perfil de Facebook',
            ],
            [
                'setting_key'     => 'social_instagram',
                'setting_value'   => 'https://www.instagram.com/',
                'setting_type'    => 'string',
                'input_type'      => 'url',
                'setting_group'   => 'social',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 20,
                'description'     => 'URL del perfil de Instagram',
            ],
            [
                'setting_key'     => 'social_twitter',
                'setting_value'   => 'https://x.com/',
                'setting_type'    => 'string',
                'input_type'      => 'url',
                'setting_group'   => 'social',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 30,
                'description'     => 'URL del perfil de Twitter / X',
            ],
            [
                'setting_key'     => 'social_linkedin',
                'setting_value'   => 'https://www.linkedin.com/',
                'setting_type'    => 'string',
                'input_type'      => 'url',
                'setting_group'   => 'social',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 40,
                'description'     => 'URL del perfil de LinkedIn',
            ],
            [
                'setting_key'     => 'social_youtube',
                'setting_value'   => 'https://www.youtube.com/',
                'setting_type'    => 'string',
                'input_type'      => 'url',
                'setting_group'   => 'social',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 50,
                'description'     => 'URL del canal de YouTube',
            ],
            [
                'setting_key'     => 'social_tiktok',
                'setting_value'   => 'https://www.tiktok.com/',
                'setting_type'    => 'string',
                'input_type'      => 'url',
                'setting_group'   => 'social',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 60,
                'description'     => 'URL del perfil de TikTok',
            ],
            [
                'setting_key'     => 'social_pinterest',
                'setting_value'   => 'https://www.pinterest.com/',
                'setting_type'    => 'string',
                'input_type'      => 'url',
                'setting_group'   => 'social',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 70,
                'description'     => 'URL del perfil de Pinterest',
            ],
            [
                'setting_key'     => 'social_github',
                'setting_value'   => 'https://github.com/',
                'setting_type'    => 'string',
                'input_type'      => 'url',
                'setting_group'   => 'social',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 80,
                'description'     => 'URL del perfil de GitHub',
            ],
        ];

        foreach ($settings as $setting) {
            $this->upsertSetting($setting);
        }

        echo "SiteSocialLinksSeeder: all social media links seeded successfully.\n";
    }

    /**
     * @param array<string, mixed> $setting
     */
    private function upsertSetting(array $setting): int
    {
        $payload = [
            'setting_value'   => $setting['setting_value'] ?? '',
            'setting_type'    => $setting['setting_type'],
            'input_type'      => $setting['input_type'] ?? 'text',
            'setting_group'   => $setting['setting_group'],
            'is_translatable' => $setting['is_translatable'],
            'is_public'       => $setting['is_public'],
            'is_active'       => $setting['is_active'],
            'sort_order'      => $setting['sort_order'],
            'description'     => $setting['description'],
        ];

        $settingId = $this->upsertRecord('cms_settings', [
            'setting_key' => $setting['setting_key'],
        ], $payload);

        if ($settingId === null) {
            throw new \RuntimeException(sprintf('SiteSocialLinksSeeder: unable to seed setting "%s".', (string) $setting['setting_key']));
        }

        return $settingId;
    }
}
