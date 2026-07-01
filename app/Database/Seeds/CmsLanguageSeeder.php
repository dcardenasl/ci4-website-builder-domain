<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the starter site's default languages (es + en).
 * Idempotent: upserts by language code.
 */
class CmsLanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'code'        => 'es',
                'name'        => 'Español',
                'native_name' => 'Español',
                'is_default'  => 1,
                'is_active'   => 1,
                'sort_order'  => 1,
            ],
            [
                'code'        => 'en',
                'name'        => 'Inglés',
                'native_name' => 'English',
                'is_default'  => 0,
                'is_active'   => 1,
                'sort_order'  => 2,
            ],
        ];

        foreach ($languages as $lang) {
            $existing = $this->db->table('cms_languages')
                ->where('code', $lang['code'])
                ->get()
                ->getRowArray();

            if ($existing === null) {
                $this->db->table('cms_languages')->insert($lang);
                echo "CmsLanguageSeeder: inserted '{$lang['code']}'.\n";
            } else {
                $this->db->table('cms_languages')
                    ->where('code', $lang['code'])
                    ->update([
                        'name'        => $lang['name'],
                        'native_name' => $lang['native_name'],
                        'is_default'  => $lang['is_default'],
                        'is_active'   => $lang['is_active'],
                        'sort_order'  => $lang['sort_order'],
                    ]);
                echo "CmsLanguageSeeder: updated '{$lang['code']}'.\n";
            }
        }
    }
}
