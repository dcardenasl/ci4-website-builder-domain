<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeCmsSettingCanonicalValues extends Migration
{
    public function up(): void
    {
        $baseLanguageId = $this->resolveBaseLanguageId();
        if ($baseLanguageId === null) {
            return;
        }

        $settings = $this->db->table('cms_settings')
            ->where('is_translatable', 1)
            ->get()
            ->getResultArray();

        foreach ($settings as $setting) {
            $settingId = (int) $setting['id'];
            $baseValue = (string) ($setting['setting_value'] ?? '');
            $baseTranslation = $this->getSettingTranslation($settingId, $baseLanguageId);

            if ($baseValue === '' && $baseTranslation !== null) {
                $translationValue = (string) ($baseTranslation['setting_value'] ?? '');
                if ($translationValue !== '') {
                    $this->db->table('cms_settings')
                        ->where('id', $settingId)
                        ->update(['setting_value' => $translationValue]);
                }
            }

            if ($baseTranslation !== null) {
                $this->db->table('cms_setting_translations')
                    ->where('setting_id', $settingId)
                    ->where('language_id', $baseLanguageId)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        $baseLanguageId = $this->resolveBaseLanguageId();
        if ($baseLanguageId === null) {
            return;
        }

        $settings = $this->db->table('cms_settings')
            ->where('is_translatable', 1)
            ->where('setting_value !=', '')
            ->get()
            ->getResultArray();

        foreach ($settings as $setting) {
            $settingId = (int) $setting['id'];
            $settingValue = (string) $setting['setting_value'];
            $existingTranslation = $this->getSettingTranslation($settingId, $baseLanguageId);

            $payload = [
                'setting_id'    => $settingId,
                'language_id'   => $baseLanguageId,
                'setting_value' => $settingValue,
            ];

            if ($existingTranslation === null) {
                $this->db->table('cms_setting_translations')->insert($payload);
                continue;
            }

            $this->db->table('cms_setting_translations')
                ->where('id', (int) $existingTranslation['id'])
                ->update(['setting_value' => $settingValue]);
        }
    }

    private function resolveBaseLanguageId(): ?int
    {
        $language = $this->db->table('cms_languages')
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();

        if ($language === null) {
            $language = $this->db->table('cms_languages')
                ->where('is_active', 1)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();
        }

        return $language !== null ? (int) $language['id'] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getSettingTranslation(int $settingId, int $languageId): ?array
    {
        $translation = $this->db->table('cms_setting_translations')
            ->where('setting_id', $settingId)
            ->where('language_id', $languageId)
            ->get()
            ->getRowArray();

        return $translation === null ? null : $translation;
    }
}
