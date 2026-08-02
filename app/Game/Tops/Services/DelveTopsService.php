<?php

namespace App\Game\Tops\Services;

use App\Flare\Models\DelveExploration;
use App\Game\Tops\Services\Concerns\BuildsTopsResponses;
use Illuminate\Http\Request;

class DelveTopsService
{
    use BuildsTopsResponses;

    public function __construct(private readonly TopsPeriodService $topsPeriodService) {}

    public function leaderboard(Request|array $request = []): array
    {
        $parameters = $request instanceof Request ? $request->query() : $request;
        $period = $this->topsPeriodService->resolve($parameters['period'] ?? null);
        $metric = $parameters['metric'] ?? 'survived_duration_seconds';
        $metrics = [
            ['key' => 'strongest_enemy_increase', 'label' => 'Total Enemy Strength %'],
            ['key' => 'survived_duration_seconds', 'label' => 'Survived Duration'],
            ['key' => 'total_floors', 'label' => 'Total Floors'],
        ];
        $snapshot = $this->snapshotResponse('delve', $metric, $period, $metrics);

        if (! is_null($snapshot)) {
            return $snapshot;
        }

        $query = DelveExploration::with(['character', 'delveLogs']);

        if ($period['key'] !== 'all_time') {
            $query->whereBetween('started_at', [$period['start'], $period['end']]);
        }

        $rows = $query->get()
            ->groupBy('character_id')
            ->map(function ($runs) {
                $character = $runs->first()->character;
                $logs = $runs->flatMap(fn (DelveExploration $run) => $run->delveLogs);
                $latest = $runs->sortByDesc('started_at')->first();

                return [
                    'rank' => 0,
                    'character_id' => $character?->id,
                    'character_name' => $character?->name,
                    'character_profile_url' => is_null($character) ? null : $this->characterProfileUrl($character->id),
                    'strongest_enemy_increase' => max((float) $runs->max('increase_enemy_strength'), (float) $logs->max('increased_enemy_strength')),
                    'run_count' => $runs->count(),
                    'encounter_count' => $logs->count(),
                    'total_floors' => $logs->count(),
                    'average_pack_size' => round((float) $logs->avg('pack_size'), 2),
                    'survived_count' => $logs->where('outcome', 'survived')->count(),
                    'died_count' => $logs->where('outcome', 'died')->count(),
                    'survived_duration_seconds' => (int) $runs->sum(fn (DelveExploration $run) => ($run->started_at && $run->completed_at) ? $run->started_at->diffInSeconds($run->completed_at) : 0),
                    'latest_run_started_at' => $latest?->started_at?->toISOString(),
                    'latest_run_completed_at' => $latest?->completed_at?->toISOString(),
                    'ended_reason' => $latest?->ended_reason,
                ];
            })
            ->filter(fn (array $row) => ! is_null($row['character_id']));

        $rows = $this->applySearch($rows, $parameters['search'] ?? null)
            ->sortByDesc($metric)
            ->values()
            ->all();

        return $this->response('delve', $metric, $period, $rows, $metrics, ['search' => $parameters['search'] ?? null]);
    }

    public function detail(DelveExploration $delveExploration): array
    {
        $delveExploration->load(['character', 'monster', 'delveLogs']);
        $logs = $delveExploration->delveLogs;

        return [
            'character' => $delveExploration->character ? [
                'id' => $delveExploration->character->id,
                'name' => $delveExploration->character->name,
                'profile_url' => $this->characterProfileUrl($delveExploration->character->id),
            ] : null,
            'monster' => $delveExploration->monster ? ['id' => $delveExploration->monster->id, 'name' => $delveExploration->monster->name] : null,
            'started_at' => $delveExploration->started_at?->toISOString(),
            'completed_at' => $delveExploration->completed_at?->toISOString(),
            'duration_seconds' => $delveExploration->started_at && $delveExploration->completed_at ? (int) $delveExploration->started_at->diffInSeconds($delveExploration->completed_at) : null,
            'attack_type' => $delveExploration->attack_type,
            'increase_enemy_strength' => $delveExploration->increase_enemy_strength,
            'ended_reason' => $delveExploration->ended_reason,
            'total_logs' => $logs->count(),
            'total_floors' => $logs->count(),
            'outcome_breakdown' => $logs->groupBy('outcome')->map->count()->all(),
            'average_pack_size' => round((float) $logs->avg('pack_size'), 2),
            'max_pack_size' => $logs->max('pack_size'),
        ];
    }
}
