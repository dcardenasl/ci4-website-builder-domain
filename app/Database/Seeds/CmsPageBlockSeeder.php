<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds block instances for Inicio and Contacto pages.
 * Idempotent: skips instances that already exist by block_id+owner_id.
 */
class CmsPageBlockSeeder extends Seeder
{
    public function run(): void
    {
        // ── Resolve IDs ──────────────────────────────────────────────────────

        $blockIds = $this->blockIds([
            'hero_slider', 'events_grid', 'news_grid',
            'page_header', 'contact_form', 'location_info', 'social_links',
        ]);

        $langIds = $this->langIds(['es', 'en']);

        // ── Inicio (page_id = 3) ──────────────────────────────────────────────

        $inicioBloques = [
            [
                'block_key'  => 'hero_slider',
                'sort_order' => 1,
                'config'     => [
                    'autoplay'          => true,
                    'interval'          => 5000,
                    'overlay_opacity'   => '0',
                    'caption_position'  => 'below',
                    'controls_position' => 'below',
                ],
                'data'       => [
                    'es' => [
                        'slide_1_image_url' => $this->placeholderSlide('Temporada 2026', '#e5e7eb', '#111827'),
                        'slide_1_heading'   => 'Temporada 2026',
                        'slide_1_subtitle'  => 'Programación destacada y actividades especiales.',
                        'slide_1_cta_label' => 'Ver programación',
                        'slide_1_cta_url'   => '/featured',
                        'slide_2_image_url' => $this->placeholderSlide('Exposiciones', '#dbeafe', '#0f172a'),
                        'slide_2_heading'   => 'Exposiciones',
                        'slide_2_subtitle'  => 'Recorridos, talleres y experiencias.',
                        'slide_2_cta_label' => 'Explorar',
                        'slide_2_cta_url'   => '/collection',
                        'slide_3_image_url' => $this->placeholderSlide('Entradas', '#f3f4f6', '#111827'),
                        'slide_3_heading'   => 'Entradas',
                        'slide_3_subtitle'  => 'Reserva tu visita en pocos pasos.',
                        'slide_3_cta_label' => 'Conocer más',
                        'slide_3_cta_url'   => '/news',
                    ],
                    'en' => [
                        'slide_1_image_url' => $this->placeholderSlide('Season 2026', '#e5e7eb', '#111827'),
                        'slide_1_heading'   => 'Season 2026',
                        'slide_1_subtitle'  => 'Featured programming and special events.',
                        'slide_1_cta_label' => 'View schedule',
                        'slide_1_cta_url'   => '/featured',
                        'slide_2_image_url' => $this->placeholderSlide('Exhibitions', '#dbeafe', '#0f172a'),
                        'slide_2_heading'   => 'Exhibitions',
                        'slide_2_subtitle'  => 'Tours, workshops and experiences.',
                        'slide_2_cta_label' => 'Explore',
                        'slide_2_cta_url'   => '/collection',
                        'slide_3_image_url' => $this->placeholderSlide('Tickets', '#f3f4f6', '#111827'),
                        'slide_3_heading'   => 'Tickets',
                        'slide_3_subtitle'  => 'Book your visit in a few steps.',
                        'slide_3_cta_label' => 'Learn more',
                        'slide_3_cta_url'   => '/news',
                    ],
                ],
            ],
            [
                'block_key'  => 'events_grid',
                'sort_order' => 2,
                'config'     => ['collection_key' => 'cartelera', 'items_limit' => 6],
                'data'       => [
                    'es' => [
                        'section_title'    => 'Nuestra Cartelera',
                        'section_subtitle' => 'Descubre los espectáculos de esta temporada',
                        'view_all_label'   => 'Ver toda la cartelera',
                        'view_all_url'     => '/cartelera',
                    ],
                    'en' => [
                        'section_title'    => 'Our Lineup',
                        'section_subtitle' => 'Explore this season\'s performances',
                        'view_all_label'   => 'View full lineup',
                        'view_all_url'     => '/cartelera',
                    ],
                ],
            ],
            [
                'block_key'  => 'news_grid',
                'sort_order' => 3,
                'config'     => ['collection_key' => 'noticias', 'items_limit' => 3],
                'data'       => [
                    'es' => [
                        'section_title'  => 'Noticias',
                        'view_all_label' => 'Ver todas las noticias',
                        'view_all_url'   => '/noticias',
                    ],
                    'en' => [
                        'section_title'  => 'News',
                        'view_all_label' => 'View all news',
                        'view_all_url'   => '/news',
                    ],
                ],
            ],
        ];

        $this->seedBlocks(3, 'page', $inicioBloques, $blockIds, $langIds);

        // ── Contacto (crear página si no existe, luego bloques) ───────────────

        $contactoId = $this->ensureContactoPage($langIds);

        $contactoBloques = [
            [
                'block_key'  => 'page_header',
                'sort_order' => 1,
                'config'     => ['bg_color' => 'bg-gray-100'],
                'data'       => [
                    'es' => [
                        'heading'          => 'Contact Us',
                        'subheading'       => 'We\'d love to hear from you',
                        'breadcrumb_label' => 'Home',
                        'breadcrumb_url'   => '/',
                    ],
                    'en' => [
                        'heading'          => 'Contact Us',
                        'subheading'       => 'We\'d love to hear from you',
                        'breadcrumb_label' => 'Home',
                        'breadcrumb_url'   => '/',
                    ],
                ],
            ],
            [
                'block_key'  => 'contact_form',
                'sort_order' => 2,
                'config'     => ['show_company' => true, 'phone_prefix' => ''],
                'data'       => [
                    'es' => [
                        'heading'         => 'Send Us a Message',
                        'submit_label'    => 'Submit',
                        'success_message' => 'Thank you! Your message was sent successfully.',
                    ],
                    'en' => [
                        'heading'         => 'Send Us a Message',
                        'submit_label'    => 'Submit',
                        'success_message' => 'Thank you! Your message was sent successfully.',
                    ],
                ],
            ],
            [
                'block_key'  => 'location_info',
                'sort_order' => 3,
                'config'     => [
                    'map_embed_url' => '',
                ],
                'data'       => [
                    'es' => [
                        'address_label' => 'Address',
                        'address'       => '123 Main Street, Your City, Country',
                        'phone_label'   => 'Phone',
                        'phone'         => '+1 (555) 000-0000',
                        'hours_label'   => 'Office Hours',
                        'hours'         => "Monday to Friday: 9:00 - 18:00\nSaturday: 10:00 - 14:00",
                    ],
                    'en' => [
                        'address_label' => 'Address',
                        'address'       => '123 Main Street, Your City, Country',
                        'phone_label'   => 'Phone',
                        'phone'         => '+1 (555) 000-0000',
                        'hours_label'   => 'Office Hours',
                        'hours'         => "Monday to Friday: 9:00 - 18:00\nSaturday: 10:00 - 14:00",
                    ],
                ],
            ],
            [
                'block_key'  => 'social_links',
                'sort_order' => 4,
                'config'     => [
                    'facebook_url'     => '',
                    'facebook_handle'  => '',
                    'instagram_url'    => '',
                    'instagram_handle' => '',
                ],
                'data'       => [
                    'es' => ['heading' => 'Follow Us'],
                    'en' => ['heading' => 'Follow Us'],
                ],
            ],
        ];

        $this->seedBlocks($contactoId, 'page', $contactoBloques, $blockIds, $langIds);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  string[] $keys
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
     * @param  string[] $codes
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
     * Create or retrieve the Contacto page and return its id.
     *
     * @param array<string, int> $langIds
     */
    private function ensureContactoPage(array $langIds): int
    {
        $existing = $this->db->table('cms_page_translations')
            ->where('slug', 'contacto')
            ->get()
            ->getRow();

        if ($existing !== null) {
            return (int) $existing->page_id;
        }

        $this->db->table('cms_pages')->insert([
            'page_type'         => 'generic',
            'status'            => 'published',
            'sort_order'        => 20,
            'sitemap_priority'  => '0.6',
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap'     => 1,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $pageId = (int) $this->db->insertID();

        $translations = [
            'es' => [
                'slug'             => 'contact',
                'title'            => 'Contact',
                'meta_title'       => 'Contact Us',
                'meta_description' => 'Get in touch with us. Contact form, address and phone.',
            ],
            'en' => [
                'slug'             => 'contact',
                'title'            => 'Contact',
                'meta_title'       => 'Contact Us',
                'meta_description' => 'Get in touch with us. Contact form, address and phone.',
            ],
        ];

        foreach ($translations as $code => $t) {
            if (! isset($langIds[$code])) {
                continue;
            }
            $this->db->table('cms_page_translations')->insert([
                'page_id'          => $pageId,
                'language_id'      => $langIds[$code],
                'slug'             => $t['slug'],
                'title'            => $t['title'],
                'meta_title'       => $t['meta_title'],
                'meta_description' => $t['meta_description'],
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        return $pageId;
    }

    /**
     * Insert block instances + translations for a page, skipping existing ones.
     *
     * @param array<array<string, mixed>> $blocks
     * @param array<string, int>          $blockIds
     * @param array<string, int>          $langIds
     */
    private function seedBlocks(int $ownerId, string $ownerType, array $blocks, array $blockIds, array $langIds): void
    {
        foreach ($blocks as $block) {
            $key     = $block['block_key'];
            $blockId = $blockIds[$key] ?? null;
            if ($blockId === null) {
                continue;
            }

            // Skip if this block_id is already assigned to this owner
            $existing = $this->db->table('cms_block_instances')
                ->where('block_id', $blockId)
                ->where('owner_type', $ownerType)
                ->where('owner_id', $ownerId)
                ->get()
                ->getRow();

            if ($existing !== null) {
                continue;
            }

            $this->db->table('cms_block_instances')->insert([
                'block_id'    => $blockId,
                'owner_type'  => $ownerType,
                'owner_id'    => $ownerId,
                'sort_order'  => $block['sort_order'],
                'is_active'   => 1,
                'block_config' => json_encode($block['config'] ?? []),
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

            $instanceId = (int) $this->db->insertID();

            foreach (($block['data'] ?? []) as $langCode => $data) {
                $langId = $langIds[$langCode] ?? null;
                if ($langId === null) {
                    continue;
                }

                $this->db->table('cms_block_instance_translations')->insert([
                    'instance_id'  => $instanceId,
                    'language_id'  => $langId,
                    'block_data'   => json_encode($data),
                    'is_published' => 1,
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }
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
