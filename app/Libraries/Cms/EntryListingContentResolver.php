<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Builds the small, stable editorial projection consumed by entry listings.
 *
 * The resolver keeps block storage details private: callers receive semantic
 * listing slots rather than a complete serialized block tree.
 */
final class EntryListingContentResolver
{
    public function __construct(private readonly BlockInstanceSerializer $blockSerializer)
    {
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return array<int, array{rich_text: string, image: array{url: string, alt: string}|null, secondary_action: array{label: string, url: string}|null}>
     */
    public function resolveBatch(array $entries, string $langCode): array
    {
        $entryIds = [];
        foreach ($entries as $entry) {
            $entryId = (int) ($entry['id'] ?? 0);
            if ($entryId > 0) {
                $entryIds[] = $entryId;
            }
        }

        $blocksByEntry = $this->blockSerializer->forOwnersBatch('entry', $entryIds, $langCode);
        $result = [];

        foreach ($entries as $entry) {
            $entryId = (int) ($entry['id'] ?? 0);
            if ($entryId <= 0) {
                continue;
            }

            $schemaListing = $this->schemaListing($entry['schema_data'] ?? null);
            $blocks = $blocksByEntry[$entryId] ?? [];

            $result[$entryId] = [
                'rich_text' => $this->stringValue($schemaListing['rich_text'] ?? null)
                    ?: $this->richTextFromBlock($blocks),
                'image' => $this->imageFromSchema($schemaListing['image'] ?? null)
                    ?? $this->imageFromBlock($blocks),
                'secondary_action' => $this->actionFromSchema($schemaListing['secondary_action'] ?? null)
                    ?? $this->actionFromBlock($blocks),
            ];
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function schemaListing(mixed $schemaData): array
    {
        if (is_string($schemaData)) {
            $schemaData = json_decode($schemaData, true);
        }
        if (is_object($schemaData)) {
            $schemaData = (array) $schemaData;
        }

        $listing = is_array($schemaData) ? ($schemaData['listing'] ?? []) : [];
        if (is_object($listing)) {
            $listing = (array) $listing;
        }

        return is_array($listing) ? $listing : [];
    }

    /** @param list<array<string, mixed>> $blocks */
    private function richTextFromBlock(array $blocks): string
    {
        $data = $this->firstBlockData($blocks, 'rich_text');

        return $this->stringValue($data['content'] ?? null);
    }

    /**
     * @param list<array<string, mixed>> $blocks
     * @return array{url: string, alt: string}|null
     */
    private function imageFromBlock(array $blocks): ?array
    {
        foreach ($blocks as $block) {
            if (($block['block_key'] ?? null) !== 'image') {
                continue;
            }

            $config = is_array($block['block_config'] ?? null) ? $block['block_config'] : [];
            $data = is_array($block['block_data'] ?? null) ? $block['block_data'] : [];
            $image = $this->imageFromSchema($config['image'] ?? null);
            if ($image !== null) {
                $image['alt'] = $this->stringValue($data['alt'] ?? $image['alt']);

                return $image;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $blocks
     * @return array{label: string, url: string}|null
     */
    private function actionFromBlock(array $blocks): ?array
    {
        $data = $this->firstBlockData($blocks, 'cta');

        return $this->actionFromSchema([
            'label' => $data['label'] ?? null,
            'url' => $data['url'] ?? null,
        ]);
    }

    /**
     * @param list<array<string, mixed>> $blocks
     * @return array<string, mixed>
     */
    private function firstBlockData(array $blocks, string $blockKey): array
    {
        foreach ($blocks as $block) {
            if (($block['block_key'] ?? null) === $blockKey && is_array($block['block_data'] ?? null)) {
                return $block['block_data'];
            }
        }

        return [];
    }

    /** @return array{url: string, alt: string}|null */
    private function imageFromSchema(mixed $value): ?array
    {
        if (is_string($value)) {
            $value = ['url' => $value];
        }
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (!is_array($value)) {
            return null;
        }

        $url = $this->stringValue($value['url'] ?? null);
        if ($url === '') {
            return null;
        }

        return ['url' => $url, 'alt' => $this->stringValue($value['alt'] ?? null)];
    }

    /** @return array{label: string, url: string}|null */
    private function actionFromSchema(mixed $value): ?array
    {
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (!is_array($value)) {
            return null;
        }

        $label = $this->stringValue($value['label'] ?? null);
        $url = $this->stringValue($value['url'] ?? null);

        return $label !== '' && $url !== '' ? ['label' => $label, 'url' => $url] : null;
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }
}
