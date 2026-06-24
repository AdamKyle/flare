<?php

namespace App\Admin\Services;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Values\AutomationType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DelveMonitoringService
{
    public function activeCharacters(): array
    {
        return CharacterAutomation::where('type', AutomationType::DELVE)
            ->where('completed_at', '>', now())
            ->with('character:id,name')
            ->get()
            ->map(function (CharacterAutomation $automation): array {
                $delve = DelveExploration::where('character_id', $automation->character_id)
                    ->whereNull('completed_at')
                    ->with('delveLogs')
                    ->first();

                $logs = $delve?->delveLogs ?? collect();
                $outcomeCounts = $logs->countBy('outcome')->all();

                return [
                    'character_id' => $automation->character_id,
                    'character_name' => $automation->character?->name,
                    'started_at' => $automation->started_at?->toDateTimeString(),
                    'increase_enemy_strength' => $delve?->increase_enemy_strength,
                    'increase_percentage' => is_null($delve) ? null : round(($delve->increase_enemy_strength ?? 0) * 100, 2),
                    'outcome_counts' => [
                        'survived' => $outcomeCounts['survived'] ?? 0,
                        'died' => $outcomeCounts['died'] ?? 0,
                        'timeout' => $outcomeCounts['timeout'] ?? 0,
                        'error' => $outcomeCounts['error'] ?? 0,
                    ],
                    'total_encounters' => $logs->count(),
                    'avg_pack_size' => $logs->count() > 0 ? round($logs->avg('pack_size'), 1) : null,
                ];
            })
            ->all();
    }

    public function recentRuns(Request $request): LengthAwarePaginator
    {
        return $this->filteredQuery($request)
            ->with([
                'character:id,name',
                'delveLogs',
            ])
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function summary(Request $request): array
    {
        $days = $this->validatedDays($request->integer('days', 7));
        $query = DelveExploration::where('started_at', '>=', now()->subDays($days));

        $logQuery = DelveLog::whereHas('delveExploration', function (Builder $q) use ($days): void {
            $q->where('started_at', '>=', now()->subDays($days));
        });

        return [
            'total_runs' => $query->clone()->count(),
            'active' => DelveExploration::whereNull('completed_at')->count(),
            'completed' => $query->clone()->whereNotNull('completed_at')->count(),
            'total_survived' => (int) $logQuery->clone()->where('outcome', 'survived')->count(),
            'total_died' => (int) $logQuery->clone()->where('outcome', 'died')->count(),
            'total_timeout' => (int) $logQuery->clone()->where('outcome', 'timeout')->count(),
        ];
    }

    public function chart(Request $request): array
    {
        $days = $this->validatedDays($request->integer('days', 7));

        return DelveExploration::where('started_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(started_at) as period, COUNT(*) as runs')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row): array => [
                'period' => $row->period,
                'runs' => (int) $row->runs,
            ])
            ->all();
    }

    private function filteredQuery(Request $request): Builder
    {
        return DelveExploration::query()
            ->when($request->filled('character_name'), function (Builder $query) use ($request): void {
                $query->whereHas(
                    'character',
                    fn (Builder $q) => $q->where('name', 'like', '%' . $request->string('character_name') . '%'),
                );
            })
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('started_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('started_at', '<=', $request->string('date_to')));
    }

    private function validatedDays(int $days): int
    {
        return in_array($days, [1, 7, 14, 30, 180, 365], true) ? $days : 7;
    }
}
