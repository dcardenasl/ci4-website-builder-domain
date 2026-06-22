<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SiteIdentitySeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'setting_key'     => 'site_name',
                'setting_value'   => '',
                'setting_type'    => 'string',
                'setting_group'   => 'identity',
                'is_translatable' => 1,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 10,
                'description'     => 'Nombre del sitio / marca',
            ],
            [
                'setting_key'     => 'site_tagline',
                'setting_value'   => '',
                'setting_type'    => 'string',
                'setting_group'   => 'identity',
                'is_translatable' => 1,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 20,
                'description'     => 'Tagline o lema del sitio',
            ],
            [
                'setting_key'     => 'site_logo',
                'setting_value'   => '',
                'setting_meta'    => null,
                'setting_type'    => 'file_id',
                'setting_group'   => 'identity',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 30,
                'description'     => 'Logo principal del sitio (file_id)',
            ],
            [
                'setting_key'     => 'favicon',
                'setting_value'   => '',
                'setting_meta'    => null,
                'setting_type'    => 'file_id',
                'setting_group'   => 'identity',
                'is_translatable' => 0,
                'is_public'       => 1,
                'is_active'       => 1,
                'sort_order'      => 40,
                'description'     => 'Favicon del sitio (file_id)',
            ],
        ];

        foreach ($settings as $setting) {
            $existing = $this->db->table('cms_settings')
                ->where('setting_key', $setting['setting_key'])
                ->get()
                ->getRowArray();

            if ($existing === null) {
                $this->db->table('cms_settings')->insert($setting);
            } else {
                $this->db->table('cms_settings')
                    ->where('setting_key', $setting['setting_key'])
                    ->update([
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
}
