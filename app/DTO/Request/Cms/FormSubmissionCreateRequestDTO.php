<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class FormSubmissionCreateRequestDTO extends BaseRequestDTO
{
    public string $form_key;
    public ?int   $page_id;
    public ?int   $language_id;
    /** @var array<string, mixed> */
    public array  $form_data;
    public string $ip_address;
    public string $user_agent;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'form_key'  => 'required|string|max_length[50]',
            'form_data' => 'required',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->form_key    = (string) ($data['form_key'] ?? 'contact');
        $this->page_id     = isset($data['page_id']) ? (int) $data['page_id'] : null;
        $this->language_id = isset($data['language_id']) ? (int) $data['language_id'] : null;
        $this->form_data   = is_array($data['form_data'] ?? null) ? $data['form_data'] : [];
        $this->ip_address  = (string) ($data['ip_address'] ?? '');
        $this->user_agent  = (string) ($data['user_agent'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'form_key'    => $this->form_key,
            'page_id'     => $this->page_id,
            'language_id' => $this->language_id,
            'form_data'   => $this->form_data,
            'ip_address'  => $this->ip_address,
            'user_agent'  => $this->user_agent,
        ];
    }
}
