<?php

namespace App\Admin\Controllers;

use App\Admin\Services\AdminStatisticsDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
    public function index()
    {

        return view('admin.statistics.dashboard');
    }

    public function dashboardData(AdminStatisticsDashboardService $service): JsonResponse
    {
        return response()->json($service->snapshot());
    }
}
