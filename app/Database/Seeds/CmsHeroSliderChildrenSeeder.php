<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds slide_banner children for the homepage hero_slider.
 * Idempotent: upserts children by parent_instance_id + sort_order.
 *
 * Depends on CmsBlockTypeSeeder (block types) and CmsPageBlockSeeder
 * (which creates the hero_slider instance on the home page).
 */
class CmsHeroSliderChildrenSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CmsBlockTypeSeeder::class);
        $this->call(CmsPageBlockSeeder::class);

        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "CmsHeroSliderChildrenSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $slideBannerId = $this->blockId('slide_banner');
        if ($slideBannerId === null) {
            echo "CmsHeroSliderChildrenSeeder: slide_banner block type not found.\n";
            return;
        }

        $heroSliderInstanceId = $this->heroSliderInstanceId();
        if ($heroSliderInstanceId === null) {
            echo "CmsHeroSliderChildrenSeeder: hero_slider instance on home page not found.\n";
            return;
        }

        $homePageId = $this->homePageId();
        if ($homePageId === null) {
            echo "CmsHeroSliderChildrenSeeder: home page not found.\n";
            return;
        }

        $slides = [
            [
                'sort_order' => 1,
                'data' => [
                    'es' => [
                        'heading'   => 'Bienvenidos a Mi Sitio',
                        'subtitle'  => 'Contenido multilingüe y gestión moderna para tu sitio web.',
                        'cta_label' => 'Conocer más',
                        'cta_url'   => '/nosotros',
                    ],
                    'en' => [
                        'heading'   => 'Welcome to My Site',
                        'subtitle'  => 'Multilingual content and modern management for your website.',
                        'cta_label' => 'Learn more',
                        'cta_url'   => '/about',
                    ],
                ],
            ],
            [
                'sort_order' => 2,
                'data' => [
                    'es' => [
                        'heading'   => 'Nuestra Historia',
                        'subtitle'  => 'Conoce el camino que hemos recorrido y los hitos que nos definen.',
                        'cta_label' => 'Ver nuestra historia',
                        'cta_url'   => '/historia',
                    ],
                    'en' => [
                        'heading'   => 'Our History',
                        'subtitle'  => 'Discover the journey we have traveled and the milestones that define us.',
                        'cta_label' => 'Read our story',
                        'cta_url'   => '/history',
                    ],
                ],
            ],
            [
                'sort_order' => 3,
                'data' => [
                    'es' => [
                        'heading'   => 'Contáctanos',
                        'subtitle'  => 'Escríbenos y te responderemos a la brevedad.',
                        'cta_label' => 'Ir al formulario',
                        'cta_url'   => '/contacto',
                    ],
                    'en' => [
                        'heading'   => 'Contact Us',
                        'subtitle'  => 'Write to us and we will reply as soon as possible.',
                        'cta_label' => 'Open form',
                        'cta_url'   => '/contact',
                    ],
                ],
            ],
        ];

        foreach ($slides as $slide) {
            $existing = $this->db->table('cms_block_instances')
                ->where('block_id', $slideBannerId)
                ->where('parent_instance_id', $heroSliderInstanceId)
                ->where('sort_order', (int) $slide['sort_order'])
                ->get()
                ->getRowArray();

            $payload = [
                'block_id'           => $slideBannerId,
                'owner_type'         => 'page',
                'owner_id'           => $homePageId,
                'parent_instance_id' => $heroSliderInstanceId,
                'sort_order'         => (int) $slide['sort_order'],
                'column_index'       => null,
                'is_active'          => 1,
                'block_config'       => json_encode([], JSON_UNESCAPED_UNICODE),
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
                    ->update(array_merge($payload, ['updated_at' => date('Y-m-d H:i:s')]));
            }

            foreach ($slide['data'] as $langCode => $data) {
                $langId = $langIds[$langCode] ?? null;
                if ($langId === null) {
                    continue;
                }
                $this->upsertTranslation($instanceId, $langId, $data);
            }
        }
    }

    private function heroSliderInstanceId(): ?int
    {
        $heroSliderBlockId = $this->blockId('hero_slider');
        if ($heroSliderBlockId === null) {
            return null;
        }

        $homePageId = $this->homePageId();
        if ($homePageId === null) {
            return null;
        }

        $row = $this->db->table('cms_block_instances')
            ->where('block_id', $heroSliderBlockId)
            ->where('owner_type', 'page')
            ->where('owner_id', $homePageId)
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    private function homePageId(): ?int
    {
        $row = $this->db->table('cms_pages')
            ->where('page_type', 'home')
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    private function blockId(string $key): ?int
    {
        $row = $this->db->table('cms_content_blocks')
            ->where('block_key', $key)
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
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

    /**
     * @param array<string, mixed> $blockData
     */
    private function upsertTranslation(int $instanceId, int $languageId, array $blockData): void
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
            'is_published' => 1,
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
            ->update(array_merge($payload, ['updated_at' => date('Y-m-d H:i:s')]));
    }
}
