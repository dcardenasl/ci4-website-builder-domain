<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'MenuItemUpdateRequest')]
readonly class MenuItemUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'menu_id', type: 'integer', nullable: true)]
    public ?int $menu_id;
    #[OA\Property(description: 'parent_id', type: 'integer', nullable: true)]
    public ?int $parent_id;
    #[OA\Property(description: 'link_type', type: 'string', nullable: true)]
    public ?string $link_type;
    #[OA\Property(description: 'page_id', type: 'integer', nullable: true)]
    public ?int $page_id;
    #[OA\Property(description: 'entry_id', type: 'integer', nullable: true)]
    public ?int $entry_id;
    #[OA\Property(description: 'collection_id', type: 'integer', nullable: true)]
    public ?int $collection_id;
    #[OA\Property(description: 'link_target', type: 'string', nullable: true)]
    public ?string $link_target;
    #[OA\Property(description: 'icon', type: 'string', nullable: true)]
    public ?string $icon;
    #[OA\Property(description: 'css_class', type: 'string', nullable: true)]
    public ?string $css_class;
    #[OA\Property(description: 'sort_order', type: 'integer', nullable: true)]
    public ?int $sort_order;
    #[OA\Property(description: 'is_active', type: 'boolean', nullable: true)]
    public ?bool $is_active;

    /**
     * @var array<array{language_id: int, label: string, custom_url?: string}>|null
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
    public ?array $translations;

    private array $mappedFields;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'menu_id'                    => 'permit_empty|integer',
            'parent_id'                  => 'permit_empty|integer',
            'link_type'                  => 'permit_empty|in_list[page,entry,collection_listing,custom_url,no_link]',
            'page_id'                    => 'permit_empty|integer',
            'entry_id'                   => 'permit_empty|integer',
            'collection_id'              => 'permit_empty|integer',
            'link_target'                => 'permit_empty|in_list[_self,_blank]',
            'icon'                       => 'permit_empty|string|max_length[50]',
            'css_class'                  => 'permit_empty|string|max_length[100]',
            'sort_order'                 => 'permit_empty|integer',
            'is_active'                  => 'permit_empty|boolean_like',
            'translations'               => 'permit_empty|array',
            'translations.*.language_id' => 'required_with[translations]|is_natural_no_zero',
            'translations.*.label'       => 'required_with[translations]|string|max_length[150]',
            'translations.*.custom_url'  => 'permit_empty|string|max_length[500]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->mappedFields = [];

        if (array_key_exists('menu_id', $data)) {
            $this->menu_id = (int) $data['menu_id'];
            $this->mappedFields['menu_id'] = $this->menu_id;
        } else {
            $this->menu_id = null;
        }

        if (array_key_exists('parent_id', $data)) {
            $this->parent_id = $data['parent_id'] !== null && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
            $this->mappedFields['parent_id'] = $this->parent_id;
        } else {
            $this->parent_id = null;
        }

        if (array_key_exists('link_type', $data)) {
            $this->link_type = (string) $data['link_type'];
            $this->mappedFields['link_type'] = $this->link_type;
        } else {
            $this->link_type = null;
        }

        if (array_key_exists('page_id', $data)) {
            $this->page_id = $data['page_id'] !== null && $data['page_id'] !== '' ? (int) $data['page_id'] : null;
            $this->mappedFields['page_id'] = $this->page_id;
        } else {
            $this->page_id = null;
        }

        if (array_key_exists('entry_id', $data)) {
            $this->entry_id = $data['entry_id'] !== null && $data['entry_id'] !== '' ? (int) $data['entry_id'] : null;
            $this->mappedFields['entry_id'] = $this->entry_id;
        } else {
            $this->entry_id = null;
        }

        if (array_key_exists('collection_id', $data)) {
            $this->collection_id = $data['collection_id'] !== null && $data['collection_id'] !== '' ? (int) $data['collection_id'] : null;
            $this->mappedFields['collection_id'] = $this->collection_id;
        } else {
            $this->collection_id = null;
        }

        if (array_key_exists('link_target', $data)) {
            $this->link_target = (string) $data['link_target'];
            $this->mappedFields['link_target'] = $this->link_target;
        } else {
            $this->link_target = null;
        }

        if (array_key_exists('icon', $data)) {
            $this->icon = $data['icon'] !== null ? (string) $data['icon'] : null;
            $this->mappedFields['icon'] = $this->icon;
        } else {
            $this->icon = null;
        }

        if (array_key_exists('css_class', $data)) {
            $this->css_class = $data['css_class'] !== null ? (string) $data['css_class'] : null;
            $this->mappedFields['css_class'] = $this->css_class;
        } else {
            $this->css_class = null;
        }

        if (array_key_exists('sort_order', $data)) {
            $this->sort_order = (int) $data['sort_order'];
            $this->mappedFields['sort_order'] = $this->sort_order;
        } else {
            $this->sort_order = null;
        }

        if (array_key_exists('is_active', $data)) {
            $this->is_active = (bool) $data['is_active'];
            $this->mappedFields['is_active'] = $this->is_active;
        } else {
            $this->is_active = null;
        }

        if (array_key_exists('translations', $data)) {
            $this->translations = (array) $data['translations'];
            $this->mappedFields['translations'] = $this->translations;
        } else {
            $this->translations = null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->mappedFields;
    }
}
