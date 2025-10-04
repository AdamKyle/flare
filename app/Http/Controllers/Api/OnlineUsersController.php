<?php

namespace App\Http\Controllers\Api;

use App\Admin\Requests\SiteAccessStatisticsRequest;
use App\Admin\Services\SiteStatisticsService;
use App\Flare\Services\SiteAccessStatisticService;
use App\Game\Core\Services\CharactersOnline;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnlineUsersController extends Controller
{
    public function __construct(
        private readonly CharactersOnline $charactersOnline,
        private readonly SiteStatisticsService $siteStatisticsService,
        private readonly SiteAccessStatisticService $siteAccessStatisticService
    ) {}

    /**
     * @throws Exception
     */
    public function getLoginDurationDetails(Request $request): JsonResponse
    {
        $filter = $request->daysPast ?? 0;

        $this->siteStatisticsService->getLogInDurationStatistics($filter);

        return response()->json([
            'stats' => [
                'labels' => $this->siteStatisticsService->labels(),
                'data' => $this->siteStatisticsService->data(),
            ],
        ]);
    }

    public function getCharactersOnline(Request $request): JsonResponse
    {
        $filter = $request->day_filter ?? 0;

        $result = $this->charactersOnline->setFilterType($filter)->getCharacterOnlineData();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function getLoginStats(SiteAccessStatisticsRequest $siteAccessStatisticsRequest): JsonResponse
    {
        $loginDetails = $this->siteAccessStatisticService->setAttribute('amount_signed_in')->setDaysPast($siteAccessStatisticsRequest->daysPast ?? 0);

        return response()->json(['stats' => $loginDetails->getSignedIn()], 200);
    }

    public function getRegistrationStats(SiteAccessStatisticsRequest $siteAccessStatisticsRequest): JsonResponse
    {
        $registrationDetails = $this->siteAccessStatisticService->setAttribute('amount_registered')->setDaysPast($siteAccessStatisticsRequest->daysPast ?? 0);

        return response()->json(['stats' => $registrationDetails->getRegistered()], 200);
    }
}
