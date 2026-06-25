<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;

/**
 * Public form definition returned to the web frontend.
 *
 * Omits internal admin-only fields (notify_email) and returns
 * only the translation data for the requested language.
 */
final readonly class FormPublicDefinitionResponseDTO implements DataTransferObjectInterface
{
    /** @var list<array<string, mixed>> */
    public array $fields;

    /**
     * @param list<array<string, mixed>> $fields Each field includes field_key, field_type, is_required, label, placeholder, help_text, error_required, error_invalid
     */
    public function __construct(
        public string  $form_key,
        public bool    $has_captcha,
        public bool    $autoreply_enabled,
        public ?string $autoreply_email_field,
        public string  $name,
        public ?string $description,
        public string  $submit_label,
        public ?string $success_message,
        public ?string $error_message,
        array          $fields = [],
    ) {
        $this->fields = $fields;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            form_key:              (string) ($data['form_key'] ?? ''),
            has_captcha:           (bool) ($data['has_captcha'] ?? false),
            autoreply_enabled:     (bool) ($data['autoreply_enabled'] ?? false),
            autoreply_email_field: isset($data['autoreply_email_field']) && $data['autoreply_email_field'] !== '' ? (string) $data['autoreply_email_field'] : null,
            name:                  (string) ($data['name'] ?? ''),
            description:           isset($data['description']) && $data['description'] !== '' ? (string) $data['description'] : null,
            submit_label:          (string) ($data['submit_label'] ?? 'Enviar'),
            success_message:       isset($data['success_message']) && $data['success_message'] !== '' ? (string) $data['success_message'] : null,
            error_message:         isset($data['error_message']) && $data['error_message'] !== '' ? (string) $data['error_message'] : null,
            fields:                is_array($data['fields'] ?? null) ? array_values($data['fields']) : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'form_key'              => $this->form_key,
            'has_captcha'           => $this->has_captcha,
            'autoreply_enabled'     => $this->autoreply_enabled,
            'autoreply_email_field' => $this->autoreply_email_field,
            'name'                  => $this->name,
            'description'           => $this->description,
            'submit_label'          => $this->submit_label,
            'success_message'       => $this->success_message,
            'error_message'         => $this->error_message,
            'fields'                => $this->fields,
        ];
    }
}
