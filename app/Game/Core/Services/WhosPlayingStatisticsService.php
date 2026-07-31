<?php

namespace App\Game\Core\Services;

use App\Admin\Services\SiteStatisticsService;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use App\Flare\Services\SiteAccessStatisticService;

class WhosPlayingStatisticsService
{
    public function __construct(
        private readonly CharactersOnline $charactersOnline,
        private readonly SiteStatisticsService $siteStatisticsService,
        private readonly SiteAccessStatisticService $siteAccessStatisticService
    ) {}

    public function snapshot(): array
    {
        $characterResult = $this->charactersOnline->setFilterType(0)->getCharacterOnlineData();

        return [
            'characters_online' => $characterResult['characters_online'],
            'signup_summary' => $this->signupSummary(),
            'login_summary' => $this->loginSummary(),
            'login_duration_chart' => $this->loginDurationChart(),
            'login_chart' => $this->siteAccessChart('amount_signed_in'),
            'registration_chart' => $this->siteAccessChart('amount_registered'),
        ];
    }

    private function signupSummary(): array
    {
        return [
            'today' => User::where('created_at', '>=', now()->startOfDay())->count(),
            'last_hour' => User::where('created_at', '>=', now()->subHour())->count(),
            'last_month' => User::where('created_at', '>=', now()->subMonth())->count(),
            'last_year' => User::where('created_at', '>=', now()->subYear())->count(),
        ];
    }

    private function loginSummary(): array
    {
        return [
            'today' => UserLoginDuration::where('logged_in_at', '>=', now()->startOfDay())->count(),
            'last_hour' => UserLoginDuration::where('logged_in_at', '>=', now()->subHour())->count(),
            'last_month' => UserLoginDuration::where('logged_in_at', '>=', now()->subMonth())->count(),
            'last_year' => UserLoginDuration::where('logged_in_at', '>=', now()->subYear())->count(),
        ];
    }

    private function loginDurationChart(): array
    {
        $this->siteStatisticsService->getLogInDurationStatistics(0);

        return [
            'labels' => $this->siteStatisticsService->labels(),
            'data' => $this->siteStatisticsService->data(),
        ];
    }

    private function siteAccessChart(string $attribute): array
    {
        $statistics = $this->siteAccessStatisticService->setAttribute($attribute)->setDaysPast(0);

        return $attribute === 'amount_signed_in'
            ? $statistics->getSignedIn()
            : $statistics->getRegistered();
    }
}
