<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

class SiteContactDefaultsSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteContactDefaultsSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $settings = [
            // `setting_value` stores the canonical base-language value.
            // Localized variants live in `cms_setting_translations`.
            [
                'setting_key'     => 'contact_admin_email',
                'setting_value'   => 'contacto@example.com',
                'setting_type'    => 'string',
                'setting_group'   => 'contact',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 10,
                'description'     => 'Correo que recibe los mensajes del formulario',
            ],
            [
                'setting_key'     => 'contact_from_email',
                'setting_value'   => 'no-reply@example.com',
                'setting_type'    => 'string',
                'setting_group'   => 'contact',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 20,
                'description'     => 'Correo remitente usado en las notificaciones',
            ],
            [
                'setting_key'     => 'contact_site_name',
                'setting_value'   => 'Mi Sitio',
                'setting_type'    => 'string',
                'setting_group'   => 'contact',
                'is_translatable' => 1,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 30,
                'description'     => 'Nombre que aparece en los emails de contacto',
                'translations'    => [
                    'en' => 'My Site',
                ],
            ],
            [
                'setting_key'     => 'contact_autoreply_message',
                'setting_value'   => 'Gracias por escribirnos. Te responderemos a la brevedad.',
                'setting_type'    => 'string',
                'setting_group'   => 'contact',
                'is_translatable' => 1,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 40,
                'description'     => 'Mensaje automático de respuesta',
                'translations'    => [
                    'en' => 'Thank you for contacting us. We will reply as soon as possible.',
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
        $payload = [
            'setting_value'   => $setting['setting_value'] ?? '',
            'setting_type'    => $setting['setting_type'],
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

        $settingId = $this->upsertRecord('cms_settings', [
            'setting_key' => $setting['setting_key'],
        ], $payload);

        if ($settingId === null) {
            throw new \RuntimeException(sprintf('SiteContactDefaultsSeeder: unable to seed setting "%s".', (string) $setting['setting_key']));
        }

        return $settingId;
    }

    private function upsertSettingTranslation(int $settingId, int $languageId, string $value): void
    {
        $this->upsertRecord('cms_setting_translations', [
            'setting_id'  => $settingId,
            'language_id' => $languageId,
        ], [
            'setting_value' => $value,
        ]);
    }
}
