<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class FormUpdateRequestDTO extends BaseRequestDTO
{
    public ?bool   $is_active;
    public ?bool   $has_captcha;
    public ?string $notify_email;
    public ?bool   $autoreply_enabled;
    public ?string $autoreply_email_field;
    /** @var array<int|string, mixed> */
    public array   $translations;

    /** @var array<string, bool> */
    private array $providedFields;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->is_active             = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null;
        $this->has_captcha           = array_key_exists('has_captcha', $data) ? (bool) $data['has_captcha'] : null;
        $this->notify_email          = array_key_exists('notify_email', $data)
            ? ($data['notify_email'] !== '' && $data['notify_email'] !== null ? (string) $data['notify_email'] : null)
            : null;
        $this->autoreply_enabled     = array_key_exists('autoreply_enabled', $data) ? (bool) $data['autoreply_enabled'] : null;
        $this->autoreply_email_field = array_key_exists('autoreply_email_field', $data)
            ? ($data['autoreply_email_field'] !== '' && $data['autoreply_email_field'] !== null ? (string) $data['autoreply_email_field'] : null)
            : null;
        $this->translations          = is_array($data['translations'] ?? null) ? $data['translations'] : [];

        $this->providedFields = [
            'notify_email'          => array_key_exists('notify_email', $data),
            'autoreply_email_field' => array_key_exists('autoreply_email_field', $data),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['translations' => $this->translations];
        if ($this->is_active !== null) {
            $data['is_active'] = $this->is_active;
        }
        if ($this->has_captcha !== null) {
            $data['has_captcha'] = $this->has_captcha;
        }
        if ($this->notify_email !== null || $this->providedFields['notify_email']) {
            $data['notify_email'] = $this->notify_email;
        }
        if ($this->autoreply_enabled !== null) {
            $data['autoreply_enabled'] = $this->autoreply_enabled;
        }
        if ($this->autoreply_email_field !== null || $this->providedFields['autoreply_email_field']) {
            $data['autoreply_email_field'] = $this->autoreply_email_field;
        }
        return $data;
    }
}
