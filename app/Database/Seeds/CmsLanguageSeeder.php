<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Seeds the starter site's default languages (es + en).
 * Idempotent: upserts by language code.
 */
class CmsLanguageSeeder extends Seeder
{
    use IdempotentSeederSupport;

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
            $this->upsertRecord('cms_languages', [
                'code' => $lang['code'],
            ], [
                'name'        => $lang['name'],
                'native_name' => $lang['native_name'],
                'is_default'  => $lang['is_default'],
                'is_active'   => $lang['is_active'],
                'sort_order'  => $lang['sort_order'],
            ]);

            echo "CmsLanguageSeeder: upserted '{$lang['code']}'.\n";
        }
    }
}
