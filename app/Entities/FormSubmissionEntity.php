<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class FormSubmissionEntity extends Entity
{
    protected $casts = [
        'id'           => 'integer',
        'form_key'     => 'string',
        'page_id'      => 'integer',
        'language_id'  => 'integer',
        'data_json'    => 'string',
        'status'       => 'string',
        'ip_address'   => 'string',
        'user_agent'   => 'string',
        'is_anonymized' => 'bool',
    ];

    protected $dates = ['created_at', 'updated_at', 'anonymized_at'];

    /**
     * Returns decoded data_json as array.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $raw = $this->attributes['data_json'] ?? '{}';
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
