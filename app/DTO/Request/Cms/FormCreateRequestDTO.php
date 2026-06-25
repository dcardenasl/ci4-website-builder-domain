<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class FormCreateRequestDTO extends BaseRequestDTO
{
    public string  $form_key;
    public bool    $is_active;
    public bool    $has_captcha;
    public ?string $notify_email;
    public bool    $autoreply_enabled;
    public ?string $autoreply_email_field;
    /** @var array<int|string, mixed> */
    public array   $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'form_key' => 'required|alpha_dash|max_length[50]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->form_key              = (string) ($data['form_key'] ?? '');
        $this->is_active             = (bool) ($data['is_active'] ?? true);
        $this->has_captcha           = (bool) ($data['has_captcha'] ?? false);
        $this->notify_email          = isset($data['notify_email']) && $data['notify_email'] !== '' ? (string) $data['notify_email'] : null;
        $this->autoreply_enabled     = (bool) ($data['autoreply_enabled'] ?? false);
        $this->autoreply_email_field = isset($data['autoreply_email_field']) && $data['autoreply_email_field'] !== '' ? (string) $data['autoreply_email_field'] : null;
        $this->translations          = is_array($data['translations'] ?? null) ? $data['translations'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'form_key'              => $this->form_key,
            'is_active'             => $this->is_active,
            'has_captcha'           => $this->has_captcha,
            'notify_email'          => $this->notify_email,
            'autoreply_enabled'     => $this->autoreply_enabled,
            'autoreply_email_field' => $this->autoreply_email_field,
            'translations'          => $this->translations,
        ];
    }
}
