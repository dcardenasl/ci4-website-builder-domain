<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SiteIdentitySeeder extends Seeder
{
    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteIdentitySeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $settings = [
            // `setting_value` stores the canonical base-language value.
            // Localized variants live in `cms_setting_translations`.
            [
                'setting_key'     => 'site_name',
                'setting_value'   => 'Mi Sitio',
                'setting_type'    => 'string',
                'input_type'      => 'text',
                'setting_group'   => 'identity',
                'is_translatable' => 1,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 10,
                'description'     => 'Nombre del sitio / marca',
                'translations'    => [
                    'en' => 'My Site',
                ],
            ],
            [
                'setting_key'     => 'site_title',
                'setting_value'   => 'Mi Sitio',
                'setting_type'    => 'string',
                'input_type'      => 'text',
                'setting_group'   => 'identity',
                'is_translatable' => 1,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 15,
                'description'     => 'Título principal del sitio',
                'translations'    => [
                    'en' => 'My Site',
                ],
            ],
            [
                'setting_key'     => 'site_tagline',
                'setting_value'   => 'Contenido multilingüe para tu sitio',
                'setting_type'    => 'string',
                'input_type'      => 'textarea',
                'setting_group'   => 'identity',
                'is_translatable' => 1,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 20,
                'description'     => 'Tagline o lema del sitio',
                'translations'    => [
                    'en' => 'Multilingual content for your website',
                ],
            ],
            [
                'setting_key'     => 'site_description',
                'setting_value'   => 'Sitio base con páginas, noticias y contacto.',
                'setting_type'    => 'string',
                'input_type'      => 'textarea',
                'setting_group'   => 'identity',
                'is_translatable' => 1,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 25,
                'description'     => 'Descripción corta del sitio',
                'translations'    => [
                    'en' => 'Starter site with pages, news, and contact.',
                ],
            ],
            [
                'setting_key'     => 'site_logo',
                'setting_value'   => '',
                'setting_meta'    => null,
                'setting_type'    => 'file_id',
                'input_type'      => 'image',
                'setting_group'   => 'identity',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 30,
                'description'     => 'Logo principal del sitio',
            ],
            [
                'setting_key'     => 'favicon',
                'setting_value'   => '',
                'setting_meta'    => null,
                'setting_type'    => 'file_id',
                'input_type'      => 'image',
                'setting_group'   => 'identity',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 40,
                'description'     => 'Favicon del sitio',
            ],
            [
                'setting_key'     => 'site_copyright',
                'setting_value'   => '© ' . date('Y') . ' Mi Sitio. Todos los derechos reservados.',
                'setting_type'    => 'string',
                'input_type'      => 'textarea',
                'setting_group'   => 'identity',
                'is_translatable' => 1,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 50,
                'description'     => 'Texto de copyright del pie de página',
                'translations'    => [
                    'en' => '© ' . date('Y') . ' My Site. All rights reserved.',
                ],
            ],
        ];

        foreach ($settings as $setting) {
            $settingId = $this->upsertSetting($setting);

            foreach (($setting['translations'] ?? []) as $langCode => $value) {
                $langId = $langIds[$langCode] ?? null;
                if ($langId === null) {
                    continue;
                }

                $this->upsertSettingTranslation($settingId, $langId, (string) $value);
            }
        }
    }

    /**
     * @param array<int, string> $codes
     * @return array<string, int>
     */
    private function langIds(array $codes): array
    {
        $rows = $this->db->table('cms_languages')
            ->whereIn('code', $codes)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['code']] = (int) $row['id'];
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $setting
     */
    private function upsertSetting(array $setting): int
    {
        $existing = $this->db->table('cms_settings')
            ->where('setting_key', $setting['setting_key'])
            ->get()
            ->getRowArray();

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

        if (array_key_exists('setting_meta', $setting)) {
            $payload['setting_meta'] = $setting['setting_meta'];
        }

        if ($existing === null) {
            $this->db->table('cms_settings')->insert(array_merge([
                'setting_key' => $setting['setting_key'],
            ], $payload));

            return (int) $this->db->insertID();
        }

        $this->db->table('cms_settings')
            ->where('id', (int) $existing['id'])
            ->update($payload);

        return (int) $existing['id'];
    }

    private function upsertSettingTranslation(int $settingId, int $languageId, string $value): void
    {
        $existing = $this->db->table('cms_setting_translations')
            ->where('setting_id', $settingId)
            ->where('language_id', $languageId)
            ->get()
            ->getRowArray();

        $payload = [
            'setting_id'    => $settingId,
            'language_id'   => $languageId,
            'setting_value' => $value,
        ];

        if ($existing === null) {
            $this->db->table('cms_setting_translations')->insert($payload);
            return;
        }

        $this->db->table('cms_setting_translations')
            ->where('id', (int) $existing['id'])
            ->update(['setting_value' => $value]);
    }
}
