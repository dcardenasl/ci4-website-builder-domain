<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds block instances for the homepage and contact page.
 * Idempotent: upserts block instances by owner + block type + sort order.
 */
class CmsPageBlockSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CmsLanguageSeeder::class);
        $this->call(CmsBlockTypeSeeder::class);
        $this->call(SitePagesSeeder::class);
        $this->call(NewsCollectionSeeder::class);

        $blockIds = $this->blockIds([
            'hero_slider',
            'news_grid',
            'cta',
            'page_header',
            'contact_form',
            'location_info',
            'social_links',
        ]);

        $langIds = $this->langIds(['es', 'en']);
        $homePageId = $this->pageIdByType('home');
        $contactPageId = $this->pageIdByType('contact');

        if ($homePageId === null || $contactPageId === null || ! isset($langIds['es'], $langIds['en'])) {
            echo "CmsPageBlockSeeder: missing prerequisite pages, blocks or languages.\n";
            return;
        }

        $homeBlocks = [
            [
                'block_key' => 'hero_slider',
                'sort_order' => 1,
                'config'    => [
                    'autoplay'          => true,
                    'interval'          => 5000,
                    'overlay_opacity'   => '20',
                    'caption_position'  => 'below',
                    'controls_position' => 'below',
                    'css_class'         => '',
                ],
                'data'      => [
                    'es' => [
                        'slide_1_image_url' => $this->placeholderSlide('Mi Sitio', '#e5e7eb', '#111827'),
                        'slide_1_heading'   => 'Mi Sitio',
                        'slide_1_subtitle'  => 'Contenido multilingüe para tu sitio.',
                        'slide_1_cta_label' => 'Conocer más',
                        'slide_1_cta_url'   => '/contacto',
                        'slide_2_image_url' => $this->placeholderSlide('Noticias', '#dbeafe', '#0f172a'),
                        'slide_2_heading'   => 'Noticias',
                        'slide_2_subtitle'  => 'Actualizaciones y novedades del sitio.',
                        'slide_2_cta_label' => 'Ver noticias',
                        'slide_2_cta_url'   => '/noticias',
                        'slide_3_image_url' => $this->placeholderSlide('Contacto', '#f3f4f6', '#111827'),
                        'slide_3_heading'   => 'Contacto',
                        'slide_3_subtitle'  => 'Escríbenos y te responderemos pronto.',
                        'slide_3_cta_label' => 'Ir al formulario',
                        'slide_3_cta_url'   => '/contacto',
                    ],
                    'en' => [
                        'slide_1_image_url' => $this->placeholderSlide('My Site', '#e5e7eb', '#111827'),
                        'slide_1_heading'   => 'My Site',
                        'slide_1_subtitle'  => 'Multilingual content for your website.',
                        'slide_1_cta_label' => 'Learn more',
                        'slide_1_cta_url'   => '/contact',
                        'slide_2_image_url' => $this->placeholderSlide('News', '#dbeafe', '#0f172a'),
                        'slide_2_heading'   => 'News',
                        'slide_2_subtitle'  => 'Updates and highlights from the site.',
                        'slide_2_cta_label' => 'View news',
                        'slide_2_cta_url'   => '/news',
                        'slide_3_image_url' => $this->placeholderSlide('Contact', '#f3f4f6', '#111827'),
                        'slide_3_heading'   => 'Contact',
                        'slide_3_subtitle'  => 'Write to us and we will reply soon.',
                        'slide_3_cta_label' => 'Open form',
                        'slide_3_cta_url'   => '/contact',
                    ],
                ],
            ],
            [
                'block_key' => 'news_grid',
                'sort_order' => 2,
                'config'    => [
                    'collection_key' => 'noticias',
                    'items_limit'    => 3,
                    'css_class'      => '',
                ],
                'data'      => [
                    'es' => [
                        'section_title'    => 'Noticias',
                        'section_subtitle' => 'Mantente al día con las últimas publicaciones.',
                        'view_all_label'   => 'Ver todas las noticias',
                        'view_all_url'     => '/noticias',
                        'empty_message'    => 'Aún no hay noticias publicadas.',
                    ],
                    'en' => [
                        'section_title'    => 'News',
                        'section_subtitle' => 'Stay up to date with the latest posts.',
                        'view_all_label'   => 'View all news',
                        'view_all_url'     => '/news',
                        'empty_message'    => 'No news posts are available yet.',
                    ],
                ],
            ],
            [
                'block_key' => 'cta',
                'sort_order' => 3,
                'config'    => [
                    'variant'   => 'blue',
                    'css_class' => '',
                ],
                'data'      => [
                    'es' => [
                        'heading' => '¿Quieres hablar con nosotros?',
                        'text'    => 'Usa el formulario de contacto para escribirnos. Te responderemos a la brevedad.',
                        'label'   => 'Ir a contacto',
                        'url'     => '/contacto',
                    ],
                    'en' => [
                        'heading' => 'Want to talk to us?',
                        'text'    => 'Use the contact form to reach out. We will reply as soon as possible.',
                        'label'   => 'Go to contact',
                        'url'     => '/contact',
                    ],
                ],
            ],
        ];

        $contactBlocks = [
            [
                'block_key' => 'page_header',
                'sort_order' => 1,
                'config'    => [
                    'bg_color'  => 'bg-gray-100',
                    'css_class' => '',
                ],
                'data'      => [
                    'es' => [
                        'heading'          => 'Contacto',
                        'subheading'       => 'Nos encantaría saber de ti.',
                        'breadcrumb_label' => 'Inicio',
                        'breadcrumb_url'   => '/',
                    ],
                    'en' => [
                        'heading'          => 'Contact',
                        'subheading'       => 'We would love to hear from you.',
                        'breadcrumb_label' => 'Home',
                        'breadcrumb_url'   => '/',
                    ],
                ],
            ],
            [
                'block_key' => 'contact_form',
                'sort_order' => 2,
                'config'    => [
                    'form_key'        => 'contact',
                    'show_info_boxes' => true,
                    'css_class'       => '',
                ],
                'data'      => [
                    'es' => [
                        'heading'          => 'Escríbenos',
                        'description'      => 'Completa el formulario y te responderemos pronto.',
                        'info_email_label' => 'Correo',
                        'info_email_desc'  => 'Usa este formulario y te contactaremos por correo.',
                        'info_phone_label' => 'Teléfono',
                        'info_phone_desc'  => 'Atención de lunes a viernes.',
                    ],
                    'en' => [
                        'heading'          => 'Write to us',
                        'description'      => 'Fill out the form and we will reply soon.',
                        'info_email_label' => 'Email',
                        'info_email_desc'  => 'Use this form and we will contact you by email.',
                        'info_phone_label' => 'Phone',
                        'info_phone_desc'  => 'Support available Monday through Friday.',
                    ],
                ],
            ],
            [
                'block_key' => 'location_info',
                'sort_order' => 3,
                'config'    => [
                    'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3329.0664974635676!2d-70.6508083!3d-33.4474867!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9662c50f83733e8b%3A0xc38fa632e825a1e7!2sPalacio%20de%20La%20Moneda!5e0!3m2!1ses!2scl!4v1700000000000!5m2!1ses!2scl',
                    'css_class'     => '',
                ],
                'data'      => [
                    'es' => [
                        'section_title' => 'Dónde estamos',
                        'address_label'  => 'Dirección',
                        'address'        => 'Calle Falsa 123, Santiago, Chile',
                        'phone_label'    => 'Teléfono',
                        'phone'          => '+56 2 2345 6789',
                        'hours_label'    => 'Horario',
                        'hours'          => "Lunes a viernes: 09:00 - 18:00\nSábado: 10:00 - 14:00",
                    ],
                    'en' => [
                        'section_title' => 'Where we are',
                        'address_label'  => 'Address',
                        'address'        => '123 Main Street, Santiago, Chile',
                        'phone_label'    => 'Phone',
                        'phone'          => '+56 2 2345 6789',
                        'hours_label'    => 'Hours',
                        'hours'          => "Monday to Friday: 09:00 - 18:00\nSaturday: 10:00 - 14:00",
                    ],
                ],
            ],
            [
                'block_key' => 'social_links',
                'sort_order' => 4,
                'config'    => [
                    'facebook_url'     => '',
                    'facebook_handle'  => '',
                    'instagram_url'    => '',
                    'instagram_handle' => '',
                    'twitter_url'      => '',
                    'twitter_handle'   => '',
                    'youtube_url'      => '',
                    'youtube_handle'   => '',
                    'css_class'        => '',
                ],
                'data'      => [
                    'es' => [
                        'heading' => 'Síguenos',
                    ],
                    'en' => [
                        'heading' => 'Follow us',
                    ],
                ],
            ],
        ];

        $this->seedBlocks($homePageId, 'page', $homeBlocks, $blockIds, $langIds);
        $this->seedBlocks($contactPageId, 'page', $contactBlocks, $blockIds, $langIds);
    }

    /**
     * @param string[] $keys
     * @return array<string, int>
     */
    private function blockIds(array $keys): array
    {
        $rows = $this->db->table('cms_content_blocks')
            ->whereIn('block_key', $keys)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['block_key']] = (int) $row['id'];
        }

        return $map;
    }

    /**
     * @param string[] $codes
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

    private function pageIdByType(string $pageType): ?int
    {
        $row = $this->db->table('cms_pages')
            ->where('page_type', $pageType)
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     * @param array<string, int>               $blockIds
     * @param array<string, int>               $langIds
     */
    private function seedBlocks(int $ownerId, string $ownerType, array $blocks, array $blockIds, array $langIds): void
    {
        foreach ($blocks as $block) {
            $blockKey = (string) $block['block_key'];
            $blockId = $blockIds[$blockKey] ?? null;
            if ($blockId === null) {
                continue;
            }

            $existing = $this->db->table('cms_block_instances')
                ->where('block_id', $blockId)
                ->where('owner_type', $ownerType)
                ->where('owner_id', $ownerId)
                ->where('sort_order', (int) $block['sort_order'])
                ->get()
                ->getRowArray();

            $payload = [
                'block_id'         => $blockId,
                'owner_type'       => $ownerType,
                'owner_id'         => $ownerId,
                'parent_instance_id' => null,
                'sort_order'       => (int) $block['sort_order'],
                'column_index'     => null,
                'is_active'        => 1,
                'block_config'     => json_encode($block['config'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];

            if ($existing === null) {
                $this->db->table('cms_block_instances')->insert(array_merge($payload, [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]));
                $instanceId = (int) $this->db->insertID();
            } else {
                $instanceId = (int) $existing['id'];
                $this->db->table('cms_block_instances')
                    ->where('id', $instanceId)
                    ->update(array_merge($payload, [
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]));
            }

            foreach (($block['data'] ?? []) as $langCode => $data) {
                $langId = $langIds[$langCode] ?? null;
                if ($langId === null || ! is_array($data)) {
                    continue;
                }

                $this->upsertBlockTranslation($instanceId, $langId, $data);
            }
        }
    }

    /**
     * @param array<string, mixed> $blockData
     */
    private function upsertBlockTranslation(int $instanceId, int $languageId, array $blockData): void
    {
        $existing = $this->db->table('cms_block_instance_translations')
            ->where('instance_id', $instanceId)
            ->where('language_id', $languageId)
            ->get()
            ->getRowArray();

        $payload = [
            'instance_id'  => $instanceId,
            'language_id'  => $languageId,
            'block_data'   => json_encode($blockData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_published'  => 1,
        ];

        if ($existing === null) {
            $this->db->table('cms_block_instance_translations')->insert(array_merge($payload, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
            return;
        }

        $this->db->table('cms_block_instance_translations')
            ->where('id', (int) $existing['id'])
            ->update(array_merge($payload, [
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
    }

    private function placeholderSlide(string $label, string $background, string $foreground): string
    {
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500"><rect width="1200" height="500" fill="%s"/><text x="50%%" y="50%%" fill="%s" font-family="Arial,Helvetica,sans-serif" font-size="56" font-weight="700" text-anchor="middle" dominant-baseline="middle">%s</text></svg>',
            htmlspecialchars($background, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            htmlspecialchars($foreground, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            htmlspecialchars($label, ENT_QUOTES | ENT_XML1, 'UTF-8')
        );

        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
    }
}
