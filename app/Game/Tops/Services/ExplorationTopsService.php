<?php

namespace App\Game\Tops\Services;

use App\Flare\Models\ExplorationLog;
use App\Game\Tops\Services\Concerns\BuildsTopsResponses;
use Illuminate\Http\Request;

class ExplorationTopsService
{
    use BuildsTopsResponses;

    public function __construct(private readonly TopsPeriodService $topsPeriodService) {}

    public function leaderboard(Request|array $request = []): array
    {
        $parameters = $request instanceof Request ? $request->query() : $request;
        $period = $this->topsPeriodService->resolve($parameters['period'] ?? null);
        $metric = $parameters['metric'] ?? 'kills';
        $metrics = [
            ['key' => 'kills', 'label' => 'Kills'],
            ['key' => 'fights', 'label' => 'Fights'],
            ['key' => 'xp_gained', 'label' => 'XP Gained'],
            ['key' => 'skill_xp_gained', 'label' => 'Skill XP'],
            ['key' => 'run_count', 'label' => 'Runs'],
        ];
        $snapshot = $this->snapshotResponse('exploration', $metric, $period, $metrics);

        if (! is_null($snapshot)) {
            return $snapshot;
        }

        $query = ExplorationLog::with('character');

        if ($period['key'] !== 'all_time') {
            $query->whereBetween('started_at', [$period['start'], $period['end']]);
        }

        $rows = $query->get()
            ->groupBy('character_id')
            ->map(function ($logs) {
                $character = $logs->first()->character;

                return [
                    'rank' => 0,
                    'character_id' => $character?->id,
                    'character_name' => $character?->name,
                    'character_profile_url' => is_null($character) ? null : $this->characterProfileUrl($character->id),
                    'kills' => $logs->sum('kills'),
                    'fights' => $logs->sum('fights'),
                    'xp_gained' => $logs->sum('xp_gained'),
                    'skill_xp_gained' => $logs->sum('skill_xp_gained'),
                    'faction_points_gained' => $logs->sum('faction_points_gained'),
                    'run_count' => $logs->count(),
                    'latest_started_at' => $logs->max('started_at')?->toISOString(),
                    'latest_ended_at' => $logs->max('ended_at')?->toISOString(),
                    'stopped_reason' => $logs->sortByDesc('started_at')->first()?->stopped_reason,
                    'attack_type' => $logs->sortByDesc('started_at')->first()?->attack_type,
                ];
            })
            ->filter(fn (array $row) => ! is_null($row['character_id']));

        $rows = $this->applySearch($rows, $parameters['search'] ?? null)
            ->sortByDesc($metric)
            ->values()
            ->all();

        return $this->response('exploration', $metric, $period, $rows, $metrics, ['search' => $parameters['search'] ?? null]);
    }

    public function detail(ExplorationLog $explorationLog): array
    {
        $explorationLog->load('character');

        return [
            'character' => $explorationLog->character ? [
                'id' => $explorationLog->character->id,
                'name' => $explorationLog->character->name,
                'profile_url' => $this->characterProfileUrl($explorationLog->character->id),
            ] : null,
            'attack_type' => $explorationLog->attack_type,
            'started_at' => $explorationLog->started_at?->toISOString(),
            'ended_at' => $explorationLog->ended_at?->toISOString(),
            'fights' => $explorationLog->fights,
            'kills' => $explorationLog->kills,
            'xp_gained' => $explorationLog->xp_gained,
            'skill_xp_gained' => $explorationLog->skill_xp_gained,
            'faction_points_gained' => $explorationLog->faction_points_gained,
            'currencies_gained' => $explorationLog->currencies_gained,
            'stopped_reason' => $explorationLog->stopped_reason,
            'stopped_by_player' => $explorationLog->stopped_by_player,
            'summary' => $explorationLog->summary,
        ];
    }
}
