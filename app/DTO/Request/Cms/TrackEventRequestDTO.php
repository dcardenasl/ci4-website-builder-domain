<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class TrackEventRequestDTO extends BaseRequestDTO
{
    public string  $url;
    public ?string $page_title;
    public ?string $referrer;
    public ?string $session_id;
    public ?string $utm_source;
    public ?string $utm_medium;
    public ?string $utm_campaign;
    public string  $device_type;
    public ?string $browser;
    public ?string $os;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'url'          => 'required|string|max_length[500]',
            'page_title'   => 'permit_empty|string|max_length[255]',
            'referrer'     => 'permit_empty|string|max_length[500]',
            'session_id'   => 'permit_empty|string|max_length[36]',
            'utm_source'   => 'permit_empty|string|max_length[100]',
            'utm_medium'   => 'permit_empty|string|max_length[100]',
            'utm_campaign' => 'permit_empty|string|max_length[100]',
            'device_type'  => 'permit_empty|in_list[desktop,mobile,tablet,bot,unknown]',
            'browser'      => 'permit_empty|string|max_length[50]',
            'os'           => 'permit_empty|string|max_length[50]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->url          = (string) ($data['url'] ?? '');
        $this->page_title   = isset($data['page_title']) ? (string) $data['page_title'] : null;
        $this->referrer     = isset($data['referrer']) && $data['referrer'] !== '' ? (string) $data['referrer'] : null;
        $this->session_id   = isset($data['session_id']) && $data['session_id'] !== '' ? (string) $data['session_id'] : null;
        $this->utm_source   = isset($data['utm_source']) && $data['utm_source'] !== '' ? (string) $data['utm_source'] : null;
        $this->utm_medium   = isset($data['utm_medium']) && $data['utm_medium'] !== '' ? (string) $data['utm_medium'] : null;
        $this->utm_campaign = isset($data['utm_campaign']) && $data['utm_campaign'] !== '' ? (string) $data['utm_campaign'] : null;
        $allowed            = ['desktop', 'mobile', 'tablet', 'bot', 'unknown'];
        $dt                 = (string) ($data['device_type'] ?? 'unknown');
        $this->device_type  = in_array($dt, $allowed, true) ? $dt : 'unknown';
        $this->browser      = isset($data['browser']) && $data['browser'] !== '' ? (string) $data['browser'] : null;
        $this->os           = isset($data['os']) && $data['os'] !== '' ? (string) $data['os'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'url'          => $this->url,
            'page_title'   => $this->page_title,
            'referrer'     => $this->referrer,
            'session_id'   => $this->session_id,
            'utm_source'   => $this->utm_source,
            'utm_medium'   => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'device_type'  => $this->device_type,
            'browser'      => $this->browser,
            'os'           => $this->os,
        ];
    }
}
