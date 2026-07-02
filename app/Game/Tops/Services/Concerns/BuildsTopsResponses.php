<?php

namespace App\Game\Tops\Services\Concerns;

use App\Flare\Models\TopsMonthlySnapshot;
use App\Game\Tops\Services\TopsPeriodService;
use Illuminate\Support\Collection;

trait BuildsTopsResponses
{
    protected function response(string $board, string $metric, array $period, array $rows, array $metrics, array $filters = [], ?string $emptyMessage = null): array
    {
        $rankedRows = collect($rows)->values()->map(function (array $row, int $index) {
            $row['rank'] = $index + 1;

            return $row;
        })->all();

        return [
            'board' => $board,
            'metric' => $metric,
            'period' => $period['key'],
            'period_label' => $period['label'],
            'generated_at' => now()->toISOString(),
            'rows' => $rankedRows,
            'podium' => array_slice($rankedRows, 0, 3),
            'available_metrics' => $metrics,
            'available_periods' => (new TopsPeriodService)->availablePeriods(),
            'filters' => $filters,
            'empty_message' => $emptyMessage ?? ($rankedRows === [] ? 'No leaderboard data is available for this period.' : null),
        ];
    }

    protected function snapshotResponse(string $board, string $metric, array $period, array $metrics): ?array
    {
        if (! $period['is_archived_month']) {
            return null;
        }

        $snapshots = TopsMonthlySnapshot::where('board_type', $board)
            ->where('metric_key', $metric)
            ->whereDate('period_start', $period['start']->toDateString())
            ->orderBy('rank')
            ->get();

        if ($snapshots->isEmpty()) {
            return null;
        }

        $rows = $snapshots->map(function (TopsMonthlySnapshot $snapshot) {
            return $snapshot->snapshot_data;
        })->all();

        return $this->response($board, $metric, $period, $rows, $metrics);
    }

    protected function characterProfileUrl(int $characterId): string
    {
        return '/game/tops/characters/'.$characterId;
    }

    protected function applySearch(Collection $rows, ?string $search): Collection
    {
        if (is_null($search) || trim($search) === '') {
            return $rows;
        }

        $needle = mb_strtolower(trim($search));

        return $rows->filter(function (array $row) use ($needle) {
            return str_contains(mb_strtolower((string) $row['character_name']), $needle);
        });
    }
}
