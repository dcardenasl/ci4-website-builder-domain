<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;

final readonly class FormSubmissionResponseDTO implements DataTransferObjectInterface
{
    /**
     * @param array<string, mixed>|null $form_data
     */
    public function __construct(
        public int     $id,
        public string  $form_key,
        public ?int    $page_id,
        public ?int    $language_id,
        public ?array  $form_data,
        public string  $status,
        public ?string $ip_address,
        public bool    $is_anonymized,
        public ?string $created_at,
        public ?string $updated_at,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawJson = $data['data_json'] ?? '{}';
        $formData = is_array($rawJson)
            ? $rawJson
            : (json_decode((string) $rawJson, true) ?: []);

        return new self(
            id:            (int) ($data['id'] ?? 0),
            form_key:      (string) ($data['form_key'] ?? ''),
            page_id:       isset($data['page_id']) ? (int) $data['page_id'] : null,
            language_id:   isset($data['language_id']) ? (int) $data['language_id'] : null,
            form_data:     $formData,
            status:        (string) ($data['status'] ?? 'new'),
            ip_address:    isset($data['ip_address']) ? (string) $data['ip_address'] : null,
            is_anonymized: (bool) ($data['is_anonymized'] ?? false),
            created_at:    isset($data['created_at']) ? (string) $data['created_at'] : null,
            updated_at:    isset($data['updated_at']) ? (string) $data['updated_at'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'form_key'     => $this->form_key,
            'page_id'      => $this->page_id,
            'language_id'  => $this->language_id,
            'form_data'    => $this->form_data,
            'status'       => $this->status,
            'ip_address'   => $this->ip_address,
            'is_anonymized' => $this->is_anonymized,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
