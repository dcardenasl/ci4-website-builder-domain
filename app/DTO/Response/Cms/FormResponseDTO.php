<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;

final readonly class FormResponseDTO implements DataTransferObjectInterface
{
    /** @var list<array<string, mixed>> */
    public array $translations;
    /** @var list<array<string, mixed>> */
    public array $fields;
    /** @var list<array<string, mixed>> */
    public array $usages;

    /**
     * @param list<array<string, mixed>> $translations
     * @param list<array<string, mixed>> $fields
     * @param list<array<string, mixed>> $usages
     */
    public function __construct(
        public int     $id,
        public string  $form_key,
        public bool    $is_active,
        public bool    $has_captcha,
        public ?string $notify_email,
        public bool    $autoreply_enabled,
        public ?string $autoreply_email_field,
        public string  $created_at,
        public string  $updated_at,
        array          $translations = [],
        array          $fields = [],
        array          $usages = [],
    ) {
        $this->translations = $translations;
        $this->fields       = $fields;
        $this->usages       = $usages;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id:                    (int) ($data['id'] ?? 0),
            form_key:              (string) ($data['form_key'] ?? ''),
            is_active:             (bool) ($data['is_active'] ?? true),
            has_captcha:           (bool) ($data['has_captcha'] ?? false),
            notify_email:          isset($data['notify_email']) && $data['notify_email'] !== '' ? (string) $data['notify_email'] : null,
            autoreply_enabled:     (bool) ($data['autoreply_enabled'] ?? false),
            autoreply_email_field: isset($data['autoreply_email_field']) && $data['autoreply_email_field'] !== '' ? (string) $data['autoreply_email_field'] : null,
            created_at:            (string) ($data['created_at'] ?? ''),
            updated_at:            (string) ($data['updated_at'] ?? ''),
            translations:          is_array($data['translations'] ?? null) ? array_values($data['translations']) : [],
            fields:                is_array($data['fields'] ?? null) ? array_values($data['fields']) : [],
            usages:                is_array($data['usages'] ?? null) ? array_values($data['usages']) : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'form_key'              => $this->form_key,
            'is_active'             => $this->is_active,
            'has_captcha'           => $this->has_captcha,
            'notify_email'          => $this->notify_email,
            'autoreply_enabled'     => $this->autoreply_enabled,
            'autoreply_email_field' => $this->autoreply_email_field,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
            'translations'          => $this->translations,
            'fields'                => $this->fields,
            'usages'                => $this->usages,
        ];
    }
}
