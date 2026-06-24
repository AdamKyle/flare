<?php

namespace App\Admin\Services;

use App\Flare\Models\FactionLoyaltyAutomation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FactionLoyaltyMonitoringService
{
    public function activeCharacters(): array
    {
        return FactionLoyaltyAutomation::whereNull('completed_at')
            ->with([
                'character:id,name',
                'factionLoyaltyNpc.npc:id,name',
                'failedBountyMonster:id,name',
                'failedFactionCraftingItem:id,name',
            ])
            ->get()
            ->map(fn (FactionLoyaltyAutomation $automation): array => [
                'character_id' => $automation->character_id,
                'character_name' => $automation->character?->name,
                'npc_name' => $automation->factionLoyaltyNpc?->npc?->name,
                'last_action' => $automation->last_automation_action,
                'last_action_at' => $automation->last_automation_action_at?->toDateTimeString(),
                'started_at' => $automation->started_at?->toDateTimeString(),
                'last_fight_outcome' => $automation->last_fight_outcome,
                'last_fight_was_bounty_target' => $automation->last_fight_was_bounty_target,
                'failed_bounty_monster_name' => $automation->failedBountyMonster?->name,
                'failed_crafting_item_name' => $automation->failedFactionCraftingItem?->name,
            ])
            ->all();
    }

    public function recentRuns(Request $request): LengthAwarePaginator
    {
        return $this->filteredQuery($request)
            ->with([
                'character:id,name',
                'factionLoyaltyNpc.npc:id,name',
                'log',
            ])
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function summary(Request $request): array
    {
        $days = $this->validatedDays($request->integer('days', 7));
        $query = FactionLoyaltyAutomation::where('started_at', '>=', now()->subDays($days));

        return [
            'total_runs' => $query->clone()->count(),
            'active' => FactionLoyaltyAutomation::whereNull('completed_at')->count(),
            'completed' => $query->clone()->whereNotNull('completed_at')->count(),
        ];
    }

    public function chart(Request $request): array
    {
        $days = $this->validatedDays($request->integer('days', 7));

        return FactionLoyaltyAutomation::where('started_at', '>=', now()->subDays($days))
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
        return FactionLoyaltyAutomation::query()
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
