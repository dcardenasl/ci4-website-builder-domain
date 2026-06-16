<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'MenuCreateRequest')]
readonly class MenuCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'menu_key', type: 'string')]
    public string $menu_key;
    #[OA\Property(description: 'location', type: 'string')]
    public string $location;
    #[OA\Property(description: 'is_active', type: 'boolean')]
    public bool $is_active;

    /**
     * @var array<array{language_id: int, name: string}>
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'menu_key'                  => 'required|string|max_length[50]',
            'location'                  => 'required|string|max_length[50]',
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
        $this->menu_key = (string) ($data['menu_key'] ?? '');
        $this->location = (string) ($data['location'] ?? '');
        $this->is_active = (bool) ($data['is_active'] ?? false);
        $this->translations = $this->normalizeTranslations($data['translations'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'menu_key'     => $this->menu_key,
            'location'     => $this->location,
            'is_active'    => $this->is_active,
            'translations' => $this->translations,
        ];
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
