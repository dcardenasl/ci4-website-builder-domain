<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Seeds social_link_item children for the contact page social_links block.
 * Idempotent: upserts children by parent_instance_id + sort_order.
 */
class CmsSocialLinksChildrenSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "CmsSocialLinksChildrenSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $socialLinkItemId = $this->blockId('social_link_item');
        if ($socialLinkItemId === null) {
            echo "CmsSocialLinksChildrenSeeder: social_link_item block type not found.\n";
            return;
        }

        $socialLinksInstanceId = $this->socialLinksInstanceId();
        if ($socialLinksInstanceId === null) {
            echo "CmsSocialLinksChildrenSeeder: social_links instance on contact page not found.\n";
            return;
        }

        $this->resetSocialLinksChildren($socialLinksInstanceId);

        $contactPageId = $this->contactPageId();
        if ($contactPageId === null) {
            echo "CmsSocialLinksChildrenSeeder: contact page not found.\n";
            return;
        }

        $links = [
            [
                'sort_order' => 1,
                'config' => [
                    'network' => 'youtube',
                    'url'     => 'https://www.youtube.com/@bencord',
                ],
                'data' => [
                    'es' => [
                        'handle' => '@bencord',
                    ],
                    'en' => [
                        'handle' => '@bencord',
                    ],
                ],
            ],
            [
                'sort_order' => 2,
                'config' => [
                    'network' => 'facebook',
                    'url'     => 'https://www.facebook.com/TeatroColonOficial/',
                ],
                'data' => [
                    'es' => [
                        'handle' => 'TeatroColonOficial',
                    ],
                    'en' => [
                        'handle' => 'TeatroColonOficial',
                    ],
                ],
            ],
            [
                'sort_order' => 3,
                'config' => [
                    'network' => 'instagram',
                    'url'     => 'https://www.instagram.com/teatrodelinstinto/',
                ],
                'data' => [
                    'es' => [
                        'handle' => '@teatrodelinstinto',
                    ],
                    'en' => [
                        'handle' => '@teatrodelinstinto',
                    ],
                ],
            ],
        ];

        foreach ($links as $link) {
            $instanceId = $this->upsertRecord('cms_block_instances', [
                'block_id'           => $socialLinkItemId,
                'owner_type'         => 'page',
                'owner_id'           => $contactPageId,
                'parent_instance_id' => $socialLinksInstanceId,
                'sort_order'         => (int) $link['sort_order'],
            ], [
                'column_index' => null,
                'is_active'    => 1,
                'block_config' => json_encode($link['config'], JSON_UNESCAPED_UNICODE),
            ]);

            if ($instanceId === null) {
                continue;
            }

            foreach ($link['data'] as $langCode => $data) {
                $langId = $langIds[$langCode] ?? null;
                if ($langId === null) {
                    continue;
                }
                $this->upsertTranslation($instanceId, $langId, $data);
            }
        }
    }

    private function resetSocialLinksChildren(int $parentInstanceId): void
    {
        $instances = $this->db->table('cms_block_instances')
            ->select('id')
            ->where('parent_instance_id', $parentInstanceId)
            ->get()
            ->getResultArray();

        if ($instances === []) {
            return;
        }

        $instanceIds = array_map(static fn (array $row): int => (int) $row['id'], $instances);
        $this->db->table('cms_block_instance_translations')->whereIn('instance_id', $instanceIds)->delete();
        $this->db->table('cms_block_instances')->whereIn('id', $instanceIds)->delete();
    }

    private function socialLinksInstanceId(): ?int
    {
        $socialLinksBlockId = $this->blockId('social_links');
        if ($socialLinksBlockId === null) {
            return null;
        }

        $contactPageId = $this->contactPageId();
        if ($contactPageId === null) {
            return null;
        }

        $row = $this->db->table('cms_block_instances')
            ->where('block_id', $socialLinksBlockId)
            ->where('owner_type', 'page')
            ->where('owner_id', $contactPageId)
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    private function contactPageId(): ?int
    {
        $row = $this->db->table('cms_pages')
            ->where('page_type', 'contact')
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
        $this->upsertRecord('cms_block_instance_translations', [
            'instance_id' => $instanceId,
            'language_id' => $languageId,
        ], [
            'block_data'   => json_encode($blockData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_published' => 1,
        ]);
    }
}
