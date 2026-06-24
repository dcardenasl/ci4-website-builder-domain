<?php

declare(strict_types=1);

namespace App\Models;

use Config\Database;

class PageViewModel
{
    private const ALLOWED_PERIODS = ['1h', '24h', '7d', '30d'];
    private const DEFAULT_PERIOD  = '7d';

    public function record(string $url, ?string $pageTitle, ?string $referrer, ?string $referrerDomain, ?string $utmSource, ?string $utmMedium, ?string $utmCampaign, string $deviceType, ?string $browser, ?string $os, ?string $sessionId): void
    {
        $db = Database::connect();
        $db->table('page_views')->insert([
            'url'             => substr($url, 0, 500),
            'page_title'      => $pageTitle !== null ? substr($pageTitle, 0, 255) : null,
            'referrer'        => $referrer !== null ? substr($referrer, 0, 500) : null,
            'referrer_domain' => $referrerDomain !== null ? substr($referrerDomain, 0, 100) : null,
            'utm_source'      => $utmSource,
            'utm_medium'      => $utmMedium,
            'utm_campaign'    => $utmCampaign,
            'device_type'     => $deviceType,
            'browser'         => $browser,
            'os'              => $os,
            'session_id'      => $sessionId,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    public function getTotalViews(string $period): int
    {
        $db     = Database::connect();
        $result = $db->table('page_views')
            ->selectCount('id', 'total')
            ->where('created_at >=', $this->periodToDatetime($period))
            ->get();

        if ($result === false) {
            return 0;
        }

        $row = $result->getRowArray();

        return (int) ($row['total'] ?? 0);
    }

    public function getUniqueVisitors(string $period): int
    {
        $db     = Database::connect();
        $result = $db->table('page_views')
            ->select('COUNT(DISTINCT session_id) as total')
            ->where('created_at >=', $this->periodToDatetime($period))
            ->where('session_id IS NOT NULL')
            ->get();

        if ($result === false) {
            return 0;
        }

        $row = $result->getRowArray();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return list<array{url: string, page_title: string|null, views: int, percentage: float}>
     */
    public function getTopPages(string $period, int $limit = 10): array
    {
        $db    = Database::connect();
        $since = $this->periodToDatetime($period);
        $total = $this->getTotalViews($period);

        $result = $db->table('page_views')
            ->select('url, page_title, COUNT(*) as views')
            ->where('created_at >=', $since)
            ->groupBy('url, page_title')
            ->orderBy('views', 'DESC')
            ->limit($limit)
            ->get();

        $rows = $result !== false ? $result->getResultArray() : [];

        return array_values(array_map(function (array $row) use ($total): array {
            return [
                'url'        => (string) $row['url'],
                'page_title' => isset($row['page_title']) ? (string) $row['page_title'] : null,
                'views'      => (int) $row['views'],
                'percentage' => $total > 0 ? round((int) $row['views'] / $total * 100, 1) : 0.0,
            ];
        }, $rows));
    }

    /**
     * @return list<array{domain: string, views: int, percentage: float}>
     */
    public function getTopReferrers(string $period, int $limit = 10): array
    {
        $db    = Database::connect();
        $since = $this->periodToDatetime($period);
        $total = $this->getTotalViews($period);

        $result = $db->table('page_views')
            ->select('referrer_domain as domain, COUNT(*) as views')
            ->where('created_at >=', $since)
            ->where('referrer_domain IS NOT NULL')
            ->groupBy('referrer_domain')
            ->orderBy('views', 'DESC')
            ->limit($limit)
            ->get();

        $rows = $result !== false ? $result->getResultArray() : [];

        return array_values(array_map(function (array $row) use ($total): array {
            return [
                'domain'     => (string) $row['domain'],
                'views'      => (int) $row['views'],
                'percentage' => $total > 0 ? round((int) $row['views'] / $total * 100, 1) : 0.0,
            ];
        }, $rows));
    }

    /**
     * @return array{desktop: int, mobile: int, tablet: int, bot: int, unknown: int}
     */
    public function getDeviceBreakdown(string $period): array
    {
        $db    = Database::connect();
        $since = $this->periodToDatetime($period);

        $result = $db->table('page_views')
            ->select('device_type, COUNT(*) as total')
            ->where('created_at >=', $since)
            ->groupBy('device_type')
            ->get();

        $rows = $result !== false ? $result->getResultArray() : [];

        $breakdown = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0, 'bot' => 0, 'unknown' => 0];
        foreach ($rows as $row) {
            $type = (string) $row['device_type'];
            if (array_key_exists($type, $breakdown)) {
                $breakdown[$type] = (int) $row['total'];
            }
        }

        return $breakdown;
    }

    /**
     * @return array<string, int>
     */
    public function getBrowserBreakdown(string $period): array
    {
        $db    = Database::connect();
        $since = $this->periodToDatetime($period);

        $result = $db->table('page_views')
            ->select('browser, COUNT(*) as total')
            ->where('created_at >=', $since)
            ->where('browser IS NOT NULL')
            ->groupBy('browser')
            ->orderBy('total', 'DESC')
            ->limit(10)
            ->get();

        $rows = $result !== false ? $result->getResultArray() : [];

        $breakdown = [];
        foreach ($rows as $row) {
            $breakdown[(string) $row['browser']] = (int) $row['total'];
        }

        return $breakdown;
    }

    /**
     * @return list<array{label: string, views: int, unique_visitors: int}>
     */
    public function getDailyTrend(string $period): array
    {
        $db    = Database::connect();
        $since = $this->periodToDatetime($period);

        $groupFormat = in_array($period, ['1h', '24h'], true)
            ? "DATE_FORMAT(created_at, '%Y-%m-%d %H:00')"
            : "DATE(created_at)";

        $result = $db->table('page_views')
            ->select("{$groupFormat} as label, COUNT(*) as views, COUNT(DISTINCT session_id) as unique_visitors")
            ->where('created_at >=', $since)
            ->groupBy($groupFormat)
            ->orderBy('label', 'ASC')
            ->get();

        $rows = $result !== false ? $result->getResultArray() : [];

        return array_values(array_map(fn (array $row): array => [
            'label'           => (string) $row['label'],
            'views'           => (int) $row['views'],
            'unique_visitors' => (int) $row['unique_visitors'],
        ], $rows));
    }

    private function periodToDatetime(string $period): string
    {
        if (!in_array($period, self::ALLOWED_PERIODS, true)) {
            $period = self::DEFAULT_PERIOD;
        }

        return match ($period) {
            '1h'  => date('Y-m-d H:i:s', strtotime('-1 hour')),
            '24h' => date('Y-m-d H:i:s', strtotime('-24 hours')),
            '7d'  => date('Y-m-d H:i:s', strtotime('-7 days')),
            '30d' => date('Y-m-d H:i:s', strtotime('-30 days')),
        };
    }
}
