<?php

namespace App\Game\Tops\Controllers\Api;

use App\Flare\Models\TopsMonthlySnapshot;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TopsMonthlySnapshotController extends Controller
{
    public function index(): JsonResponse
    {
        $months = TopsMonthlySnapshot::query()
            ->select('period_start', 'period_end')
            ->distinct()
            ->orderByDesc('period_start')
            ->get()
            ->map(fn (TopsMonthlySnapshot $snapshot) => [
                'period' => $snapshot->period_start->format('Y-m'),
                'period_start' => $snapshot->period_start->toDateString(),
                'period_end' => $snapshot->period_end->toDateString(),
                'label' => $snapshot->period_start->format('F Y'),
            ])
            ->values()
            ->all();

        return response()->json(['months' => $months]);
    }
}
