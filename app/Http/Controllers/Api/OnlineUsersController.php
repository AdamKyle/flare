<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Admin\Services\SiteStatisticsService;
use App\Game\Core\Services\CharactersOnline;
use Illuminate\Http\JsonResponse;

class OnlineUsersController extends Controller {

    public function __construct(private readonly CharactersOnline $charactersOnline, private readonly SiteStatisticsService $siteStatisticsService) {
    }

    /**
     * @param Request $request
     * @return JsonResponse
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

    public function getCharactersOnline(Request $request) {
        $filter = $request->day_filter ?? 0;

        $result = $this->charactersOnline->setFilterType($filter)->getCharacterOnlineData();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
