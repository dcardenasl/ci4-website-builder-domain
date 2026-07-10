<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class PublicEntryShowRequestDTO extends BaseRequestDTO
{
    public string $lang;
    public string $collection_key;
    public string $slug;
    public ?bool $preview;
    public ?string $preview_expires;
    public ?string $preview_sig;

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'lang'            => 'required|string|max_length[10]',
            'collection_key'  => 'required|string|max_length[50]',
            'slug'            => 'required|string|max_length[255]',
            'preview'         => 'permit_empty|in_list[0,1]',
            'preview_expires' => 'permit_empty|string|max_length[20]',
            'preview_sig'     => 'permit_empty|string|max_length[64]',
        ];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
        $this->lang            = (string) ($data['lang'] ?? '');
        $this->collection_key  = (string) ($data['collection_key'] ?? '');
        $this->slug             = (string) ($data['slug'] ?? '');
        $this->preview          = isset($data['preview']) ? filter_var($data['preview'], FILTER_VALIDATE_BOOL) : null;
        $this->preview_expires = isset($data['preview_expires']) ? (string) $data['preview_expires'] : null;
        $this->preview_sig     = isset($data['preview_sig']) ? (string) $data['preview_sig'] : null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'lang'            => $this->lang,
            'collection_key'  => $this->collection_key,
            'slug'            => $this->slug,
            'preview'         => $this->preview,
            'preview_expires' => $this->preview_expires,
            'preview_sig'     => $this->preview_sig,
        ];
    }
}
