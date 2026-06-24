<?php

namespace App\Admin\Services;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\ExplorationLog;
use App\Flare\Values\AutomationType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ExplorationMonitoringService
{
    public function activeCharacters(): array
    {
        return CharacterAutomation::where('type', AutomationType::EXPLORING)
            ->where('completed_at', '>', now())
            ->with('character:id,name', 'monster:id,name')
            ->get()
            ->map(fn (CharacterAutomation $automation): array => [
                'character_id' => $automation->character_id,
                'character_name' => $automation->character?->name,
                'monster_name' => $automation->monster?->name,
                'attack_type' => $automation->attack_type,
                'started_at' => $automation->started_at?->toDateTimeString(),
                'completed_at' => $automation->completed_at?->toDateTimeString(),
            ])
            ->all();
    }

    public function recentLogs(Request $request): LengthAwarePaginator
    {
        return $this->filteredLogs($request)
            ->with('character:id,name')
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function summary(Request $request): array
    {
        $days = $this->validatedDays($request->integer('days', 7));
        $query = ExplorationLog::where('started_at', '>=', now()->subDays($days));

        return [
            'total_runs' => $query->clone()->count(),
            'stopped_by_player' => $query->clone()->where('stopped_by_player', true)->count(),
            'total_kills' => (int) $query->clone()->sum('kills'),
            'total_xp_gained' => (int) $query->clone()->sum('xp_gained'),
            'total_skill_xp_gained' => (int) $query->clone()->sum('skill_xp_gained'),
            'total_weapon_damage' => (int) $query->clone()->sum('weapon_damage'),
            'total_spell_damage' => (int) $query->clone()->sum('spell_damage'),
            'total_faction_points_gained' => (int) $query->clone()->sum('faction_points_gained'),
        ];
    }

    public function chart(Request $request): array
    {
        $days = $this->validatedDays($request->integer('days', 7));

        return ExplorationLog::where('started_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(started_at) as period, COUNT(*) as runs, SUM(kills) as kills, SUM(xp_gained) as xp')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row): array => [
                'period' => $row->period,
                'runs' => (int) $row->runs,
                'kills' => (int) $row->kills,
                'xp' => (int) $row->xp,
            ])
            ->all();
    }

    private function filteredLogs(Request $request): Builder
    {
        return ExplorationLog::query()
            ->when($request->filled('character_name'), function (Builder $query) use ($request): void {
                $query->whereHas(
                    'character',
                    fn (Builder $q) => $q->where('name', 'like', '%' . $request->string('character_name') . '%'),
                );
            })
            ->when($request->filled('stopped_reason'), fn (Builder $q) => $q->where('stopped_reason', 'like', '%' . $request->string('stopped_reason') . '%'))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('started_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('started_at', '<=', $request->string('date_to')));
    }

    private function validatedDays(int $days): int
    {
        return in_array($days, [1, 7, 14, 30, 180, 365], true) ? $days : 7;
    }
}
