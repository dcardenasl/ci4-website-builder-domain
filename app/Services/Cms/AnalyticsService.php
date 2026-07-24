<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\TrackEventRequestDTO;
use App\Models\PageViewModel;

class AnalyticsService
{
    private const ALLOWED_PERIODS = ['1h', '24h', '7d', '30d'];
    private const DEFAULT_PERIOD  = '7d';

    public function __construct(private PageViewModel $model)
    {
    }

    public function record(TrackEventRequestDTO $dto): void
    {
        $referrerDomain = null;
        if ($dto->referrer !== null && $dto->referrer !== '') {
            $host = parse_url($dto->referrer, PHP_URL_HOST);
            $referrerDomain = is_string($host) ? strtolower($host) : null;
        }

        $this->model->record(
            url: $dto->url,
            pageTitle: $dto->page_title,
            referrer: $dto->referrer,
            referrerDomain: $referrerDomain,
            utmSource: $dto->utm_source,
            utmMedium: $dto->utm_medium,
            utmCampaign: $dto->utm_campaign,
            deviceType: $dto->device_type,
            browser: $dto->browser,
            os: $dto->os,
            sessionId: $dto->session_id,
        );
    }

    /**
     * @return array{total_views: int, unique_visitors: int, top_page: string|null, top_referrer: string|null, period: string}
     */
    public function getOverview(string $period): array
    {
        $period      = $this->normalizePeriod($period);
        $totalViews  = $this->model->getTotalViews($period);
        $uniqueVis   = $this->model->getUniqueVisitors($period);
        $topPages    = $this->model->getTopPages($period, 1);
        $topReferrers = $this->model->getTopReferrers($period, 1);

        return [
            'total_views'      => $totalViews,
            'unique_visitors'  => $uniqueVis,
            'top_page'         => $topPages !== [] ? $topPages[0]['url'] : null,
            'top_page_title'   => $topPages !== [] ? $topPages[0]['page_title'] : null,
            'top_referrer'     => $topReferrers !== [] ? $topReferrers[0]['domain'] : null,
            'period'           => $period,
        ];
    }

    /**
     * @return array{data: list<array{url: string, page_title: string|null, views: int, percentage: float}>, period: string}
     */
    public function getTopPages(string $period, int $limit = 10): array
    {
        $period = $this->normalizePeriod($period);

        return [
            'data'   => $this->model->getTopPages($period, min($limit, 50)),
            'period' => $period,
        ];
    }

    /**
     * @return array{data: list<array{domain: string, views: int, percentage: float}>, period: string}
     */
    public function getTopReferrers(string $period, int $limit = 10): array
    {
        $period = $this->normalizePeriod($period);

        return [
            'data'   => $this->model->getTopReferrers($period, min($limit, 50)),
            'period' => $period,
        ];
    }

    /**
     * @return array{desktop: int, mobile: int, tablet: int, bot: int, unknown: int, period: string}
     */
    public function getDeviceBreakdown(string $period): array
    {
        $period    = $this->normalizePeriod($period);
        $breakdown = $this->model->getDeviceBreakdown($period);

        return [
            'desktop' => $breakdown['desktop'],
            'mobile'  => $breakdown['mobile'],
            'tablet'  => $breakdown['tablet'],
            'bot'     => $breakdown['bot'],
            'unknown' => $breakdown['unknown'],
            'period'  => $period,
        ];
    }

    /**
     * @return array{data: list<array{label: string, views: int, unique_visitors: int}>, period: string}
     */
    public function getTimeseries(string $period): array
    {
        $period = $this->normalizePeriod($period);

        return [
            'data'   => $this->model->getDailyTrend($period),
            'period' => $period,
        ];
    }

    private function normalizePeriod(string $period): string
    {
        return in_array($period, self::ALLOWED_PERIODS, true) ? $period : self::DEFAULT_PERIOD;
    }
}
