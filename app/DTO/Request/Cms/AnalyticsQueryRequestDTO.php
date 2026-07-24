<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class AnalyticsQueryRequestDTO extends BaseRequestDTO
{
    public string $period;
    public int    $limit;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'period' => 'permit_empty|in_list[1h,24h,7d,30d]',
            'limit'  => 'permit_empty|integer|greater_than[0]|less_than_equal_to[50]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $allowed       = ['1h', '24h', '7d', '30d'];
        $period        = (string) ($data['period'] ?? '7d');
        $this->period  = in_array($period, $allowed, true) ? $period : '7d';
        $this->limit   = isset($data['limit']) ? max(1, min(50, (int) $data['limit'])) : 10;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'limit'  => $this->limit,
        ];
    }
}
