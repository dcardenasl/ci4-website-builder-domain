<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CmsBlockTypeSeeder extends Seeder
{
    public function run(): void
    {
        $blocks = [
            [
                'block_key'         => 'rich_text',
                'name'              => 'Rich Text',
                'description'       => 'A standard block for WYSIWYG formatted text.',
                'category'          => 'content',
                'icon'              => 'align-left',
                'schema_definition' => json_encode([
                    'fields' => [
                        'content' => [
                            'type'     => 'text',
                            'required' => true,
                            'label'    => 'Content',
                        ],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 1,
                'is_container'     => 0,
                'is_active'         => 1,
                'sort_order'        => 10,
            ],
            [
                'block_key'         => 'image',
                'name'              => 'Image',
                'description'       => 'Displays a single image linked from the Media library.',
                'category'          => 'media',
                'icon'              => 'image',
                'schema_definition' => json_encode([
                    'fields' => [
                        'file_id' => [
                            'type'     => 'integer',
                            'required' => true,
                            'label'    => 'Image File ID',
                        ],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 1,
                'is_container'     => 0,
                'is_active'         => 1,
                'sort_order'        => 20,
            ],
            [
                'block_key'         => 'cta',
                'name'              => 'Call to Action (CTA)',
                'description'       => 'A box with a title, description, and action button.',
                'category'          => 'marketing',
                'icon'              => 'mouse-pointer',
                'schema_definition' => json_encode([
                    'fields' => [
                        'title' => [
                            'type'     => 'string',
                            'required' => true,
                            'label'    => 'Title',
                        ],
                        'description' => [
                            'type'     => 'text',
                            'required' => false,
                            'label'    => 'Description',
                        ],
                        'button_text' => [
                            'type'     => 'string',
                            'required' => true,
                            'label'    => 'Button Text',
                        ],
                        'button_url' => [
                            'type'     => 'string',
                            'required' => true,
                            'label'    => 'Button URL',
                        ],
                        'style' => [
                            'type'    => 'select',
                            'options' => ['primary', 'secondary'],
                            'default' => 'primary',
                            'label'   => 'Style',
                        ],
                    ],
                ]),
                'supports_pages'   => 1,
                'supports_entries' => 1,
                'is_container'     => 0,
                'is_active'         => 1,
                'sort_order'        => 30,
            ],
        ];

        foreach ($blocks as $block) {
            $existing = $this->db->table('cms_content_blocks')
                ->where('block_key', $block['block_key'])
                ->get()
                ->getRow();

            if ($existing === null) {
                $this->db->table('cms_content_blocks')->insert($block);
            } else {
                $this->db->table('cms_content_blocks')
                    ->where('id', $existing->id)
                    ->update($block);
            }
        }
    }
}
