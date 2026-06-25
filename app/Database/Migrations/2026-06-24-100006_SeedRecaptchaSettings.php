<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedRecaptchaSettings extends Migration
{
    public function up(): void
    {
        $this->insertSettingIfMissing(
            'recaptcha_site_key',
            'integration',
            'Clave pública de reCAPTCHA usada por el sitio web.',
            true,
            900
        );

        $this->insertSettingIfMissing(
            'recaptcha_secret_key',
            'integration',
            'Clave secreta de reCAPTCHA usada por el Domain para validar envíos.',
            false,
            901
        );
    }

    public function down(): void
    {
        $this->db->table('cms_settings')
            ->whereIn('setting_key', ['recaptcha_site_key', 'recaptcha_secret_key'])
            ->delete();
    }

    private function insertSettingIfMissing(
        string $key,
        string $group,
        string $description,
        bool $isPublic,
        int $sortOrder
    ): void {
        $exists = $this->db->table('cms_settings')
            ->where('setting_key', $key)
            ->countAllResults();

        if ($exists > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('cms_settings')->insert([
            'setting_key'     => $key,
            'setting_value'   => '',
            'setting_type'    => 'string',
            'setting_group'   => $group,
            'is_translatable' => 0,
            'is_public'       => $isPublic ? 1 : 0,
            'is_active'       => 1,
            'sort_order'      => $sortOrder,
            'description'     => $description,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }
}
