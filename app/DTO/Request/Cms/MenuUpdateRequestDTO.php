<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'MenuUpdateRequest')]
readonly class MenuUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'menu_key', type: 'string', nullable: true)]
    public ?string $menu_key;
    #[OA\Property(description: 'location', type: 'string', nullable: true)]
    public ?string $location;
    #[OA\Property(description: 'is_active', type: 'boolean', nullable: true)]
    public ?bool $is_active;

    /**
     * @var array<array{language_id: int, name: string}>|null
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
    public ?array $translations;

    /** @var array<string, mixed> */
    private array $mappedFields;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'menu_key'                  => 'permit_empty|string|max_length[50]',
            'location'                  => 'permit_empty|string|max_length[50]',
            'is_active'                 => 'permit_empty|boolean_like',
            'translations'              => 'permit_empty',
            'translations.*.language_id' => 'permit_empty|is_natural_no_zero',
            'translations.*.name'       => 'permit_empty|string|max_length[150]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $mappedFields = [];

        if (array_key_exists('menu_key', $data)) {
            $this->menu_key = (string) $data['menu_key'];
            $mappedFields['menu_key'] = $this->menu_key;
        } else {
            $this->menu_key = null;
        }

        if (array_key_exists('location', $data)) {
            $this->location = (string) $data['location'];
            $mappedFields['location'] = $this->location;
        } else {
            $this->location = null;
        }

        if (array_key_exists('is_active', $data)) {
            $this->is_active = (bool) $data['is_active'];
            $mappedFields['is_active'] = $this->is_active;
        } else {
            $this->is_active = null;
        }

        if (array_key_exists('translations', $data)) {
            $this->translations = $this->normalizeTranslations((array) $data['translations']);
            $mappedFields['translations'] = $this->translations;
        } else {
            $this->translations = null;
        }

        $this->mappedFields = $mappedFields;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->mappedFields;
    }

    /**
     * @param array<mixed> $translations
     * @return array<int, array{language_id: int, name: string}>
     */
    private function normalizeTranslations(array $translations): array
    {
        return array_values(array_filter(
            array_map(
                static function (mixed $translation): ?array {
                    if (! is_array($translation)) {
                        return null;
                    }

                    $languageId = isset($translation['language_id']) ? (int) $translation['language_id'] : 0;
                    $name = trim((string) ($translation['name'] ?? ''));

                    if ($languageId <= 0 || $name === '') {
                        return null;
                    }

                    return [
                        'language_id' => $languageId,
                        'name' => $name,
                    ];
                },
                $translations
            ),
            static fn (?array $translation): bool => $translation !== null
        ));
    }
}
