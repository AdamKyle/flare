<?php

namespace App\Http\Controllers\Api;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Admin\Services\SiteStatisticsService;
use App\Game\Core\Services\CharactersOnline;
use App\Admin\Requests\SiteAccessStatisticsRequest;
use App\Flare\Services\SiteAccessStatisticService;


class OnlineUsersController extends Controller {

    public function __construct(
        private readonly CharactersOnline $charactersOnline,
        private readonly SiteStatisticsService $siteStatisticsService,
        private readonly SiteAccessStatisticService $siteAccessStatisticService
    ) {}

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getLoginDurationDetails(Request $request): JsonResponse {
        $filter = $request->daysPast ?? 0;

        $this->siteStatisticsService->getLogInDurationStatistics($filter);

        return response()->json([
            'stats' => [
                'labels' => $this->siteStatisticsService->labels(),
                'data' => $this->siteStatisticsService->data(),
            ],
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCharactersOnline(Request $request): JsonResponse {
        $filter = $request->day_filter ?? 0;

        $result = $this->charactersOnline->setFilterType($filter)->getCharacterOnlineData();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param SiteAccessStatisticsRequest $siteAccessStatisticsRequest
     * @return JsonResponse
     */
    public function getLoginStats(SiteAccessStatisticsRequest $siteAccessStatisticsRequest): JsonResponse {
        $loginDetails = $this->siteAccessStatisticService->setAttribute('amount_signed_in')->setDaysPast($request->daysPast ?? 0);

        return response()->json(['stats' => $loginDetails->getSignedIn()], 200);
    }

    /**
     * @param SiteAccessStatisticsRequest $siteAccessStatisticsRequest
     * @return JsonResponse
     */
    public function getRegistrationStats(SiteAccessStatisticsRequest $siteAccessStatisticsRequest): JsonResponse {
        $registrationDetails = $this->siteAccessStatisticService->setAttribute('amount_registered')->setDaysPast($request->daysPast ?? 0);

        return response()->json(['stats' => $registrationDetails->getRegistered()], 200);
    }
}
