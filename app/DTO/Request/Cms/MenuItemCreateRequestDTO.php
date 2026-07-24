<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'MenuItemCreateRequest')]
readonly class MenuItemCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'menu_id', type: 'integer')]
    public int $menu_id;
    #[OA\Property(description: 'parent_id', type: 'integer', nullable: true)]
    public ?int $parent_id;
    #[OA\Property(description: 'link_type', type: 'string')]
    public string $link_type;
    #[OA\Property(description: 'page_id', type: 'integer', nullable: true)]
    public ?int $page_id;
    #[OA\Property(description: 'entry_id', type: 'integer', nullable: true)]
    public ?int $entry_id;
    #[OA\Property(description: 'collection_id', type: 'integer', nullable: true)]
    public ?int $collection_id;
    #[OA\Property(description: 'link_target', type: 'string')]
    public string $link_target;
    #[OA\Property(description: 'icon', type: 'string', nullable: true)]
    public ?string $icon;
    #[OA\Property(description: 'css_class', type: 'string', nullable: true)]
    public ?string $css_class;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public int $sort_order;
    #[OA\Property(description: 'is_active', type: 'boolean')]
    public bool $is_active;

    /**
     * @var array<array{language_id: int, label: string, custom_url?: string}>
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'menu_id'                    => 'required|integer',
            'parent_id'                  => 'permit_empty|integer',
            'link_type'                  => 'required|in_list[page,entry,collection_listing,custom_url,no_link]',
            'page_id'                    => 'permit_empty|integer',
            'entry_id'                   => 'permit_empty|integer',
            'collection_id'              => 'permit_empty|integer',
            'link_target'                => 'required|in_list[_self,_blank]',
            'icon'                       => 'permit_empty|string|max_length[50]',
            'css_class'                  => 'permit_empty|string|max_length[100]',
            'sort_order'                 => 'required|integer',
            'is_active'                  => 'permit_empty|boolean_like',
            'translations'               => 'permit_empty',
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
        $this->menu_id = (int) ($data['menu_id'] ?? 0);
        $this->parent_id = isset($data['parent_id']) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
        $this->link_type = (string) ($data['link_type'] ?? '');
        $this->page_id = isset($data['page_id']) && $data['page_id'] !== '' ? (int) $data['page_id'] : null;
        $this->entry_id = isset($data['entry_id']) && $data['entry_id'] !== '' ? (int) $data['entry_id'] : null;
        $this->collection_id = isset($data['collection_id']) && $data['collection_id'] !== '' ? (int) $data['collection_id'] : null;
        $this->link_target = (string) ($data['link_target'] ?? '_self');
        $this->icon = isset($data['icon']) ? (string) $data['icon'] : null;
        $this->css_class = isset($data['css_class']) ? (string) $data['css_class'] : null;
        $this->sort_order = (int) ($data['sort_order'] ?? 0);
        $this->is_active = (bool) ($data['is_active'] ?? false);
        $this->translations = $data['translations'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'menu_id'       => $this->menu_id,
            'parent_id'     => $this->parent_id,
            'link_type'     => $this->link_type,
            'page_id'       => $this->page_id,
            'entry_id'      => $this->entry_id,
            'collection_id' => $this->collection_id,
            'link_target'   => $this->link_target,
            'icon'          => $this->icon,
            'css_class'     => $this->css_class,
            'sort_order'    => $this->sort_order,
            'is_active'     => $this->is_active,
            'translations'  => $this->translations,
        ];
    }
}
