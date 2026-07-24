<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class FormSubmissionCreateRequestDTO extends BaseRequestDTO
{
    public string  $form_key;
    public ?int    $form_id;
    public ?int    $page_id;
    public ?int    $language_id;
    /** @var array<string, mixed> */
    public array   $form_data;
    public string  $ip_address;
    public string  $user_agent;
    public ?string $captcha_token;

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
        $this->form_key      = (string) ($data['form_key'] ?? 'contact');
        $this->page_id       = isset($data['page_id']) ? (int) $data['page_id'] : null;
        $this->language_id   = isset($data['language_id']) ? (int) $data['language_id'] : null;
        $this->form_data     = is_array($data['form_data'] ?? null) ? $data['form_data'] : [];
        $this->ip_address    = (string) ($data['ip_address'] ?? '');
        $this->user_agent    = (string) ($data['user_agent'] ?? '');
        $this->captcha_token = isset($data['captcha_token']) && $data['captcha_token'] !== '' ? (string) $data['captcha_token'] : null;

        // Resolve form_id from form_key
        /** @var \App\Models\FormModel $formModel */
        $formModel      = model(\App\Models\FormModel::class);
        $form           = $formModel->where('form_key', $this->form_key)->where('is_active', 1)->first();
        $this->form_id  = $form !== null ? (int) ($form->id ?? 0) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'form_key'      => $this->form_key,
            'form_id'       => $this->form_id,
            'page_id'       => $this->page_id,
            'language_id'   => $this->language_id,
            'form_data'     => $this->form_data,
            'ip_address'    => $this->ip_address,
            'user_agent'    => $this->user_agent,
            'captcha_token' => $this->captcha_token,
        ];
    }
}
