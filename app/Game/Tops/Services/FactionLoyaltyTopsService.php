<?php

namespace App\Game\Tops\Services;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Game\Tops\Services\Concerns\BuildsTopsResponses;
use Illuminate\Http\Request;

class FactionLoyaltyTopsService
{
    use BuildsTopsResponses;

    public function __construct(private readonly TopsPeriodService $topsPeriodService) {}

    public function leaderboard(Request|array $request = []): array
    {
        $parameters = $request instanceof Request ? $request->query() : $request;
        $period = $this->topsPeriodService->resolve($parameters['period'] ?? null);
        $metric = $parameters['metric'] ?? 'highest_faction_level';
        $metrics = [
            ['key' => 'highest_faction_level', 'label' => 'Highest Faction Level'],
            ['key' => 'maxed_faction_count', 'label' => 'Maxed Factions'],
            ['key' => 'highest_npc_loyalty_level', 'label' => 'Highest NPC Loyalty'],
            ['key' => 'automation_run_count', 'label' => 'Automation Runs'],
        ];
        $snapshot = $this->snapshotResponse('faction-loyalty', $metric, $period, $metrics);

        if (! is_null($snapshot)) {
            return $snapshot;
        }

        if ($period['is_archived_month']) {
            return $this->response('faction-loyalty', $metric, $period, [], $metrics, [], 'Historical faction loyalty progression requires a monthly snapshot for this archived month.');
        }

        $rows = Character::with(['factions', 'factionLoyalties.factionLoyaltyNpcs'])->get()->map(function (Character $character) {
            $automations = FactionLoyaltyAutomation::where('character_id', $character->id)->get();
            $latestAutomation = $automations->sortByDesc('last_automation_action_at')->first();
            $npcs = $character->factionLoyalties->flatMap(fn ($loyalty) => $loyalty->factionLoyaltyNpcs);

            return [
                'rank' => 0,
                'character_id' => $character->id,
                'character_name' => $character->name,
                'character_profile_url' => $this->characterProfileUrl($character->id),
                'highest_faction_level' => (int) $character->factions->max('current_level'),
                'highest_faction_points' => (int) $character->factions->max('current_points'),
                'maxed_faction_count' => $character->factions->where('maxed', true)->count(),
                'pledged_faction_count' => $character->factionLoyalties->where('is_pledged', true)->count(),
                'highest_npc_loyalty_level' => (int) $npcs->max('current_level'),
                'automation_run_count' => $automations->count(),
                'latest_action' => $latestAutomation?->last_automation_action,
                'latest_outcome' => $latestAutomation?->last_fight_outcome,
            ];
        })->filter(fn (array $row) => $row['highest_faction_level'] > 0 || $row['automation_run_count'] > 0);

        $rows = $this->applySearch($rows, $parameters['search'] ?? null)
            ->sortByDesc('highest_faction_points')
            ->sortByDesc($metric)
            ->values()
            ->all();

        return $this->response('faction-loyalty', $metric, $period, $rows, $metrics, ['search' => $parameters['search'] ?? null]);
    }

    public function detail(Character $character): array
    {
        $character->load(['factions.gameMap', 'factionLoyalties.faction', 'factionLoyalties.factionLoyaltyNpcs.npc']);
        $automations = FactionLoyaltyAutomation::where('character_id', $character->id)->with('log')->latest('last_automation_action_at')->get();

        return [
            'character' => ['id' => $character->id, 'name' => $character->name, 'profile_url' => $this->characterProfileUrl($character->id)],
            'factions' => $character->factions->map(fn ($faction) => [
                'map' => $faction->gameMap?->name,
                'current_level' => $faction->current_level,
                'current_points' => $faction->current_points,
                'points_needed' => $faction->points_needed,
                'maxed' => $faction->maxed,
                'title' => $faction->title,
            ])->values()->all(),
            'loyalties' => $character->factionLoyalties->map(fn ($loyalty) => [
                'is_pledged' => $loyalty->is_pledged,
                'faction_id' => $loyalty->faction_id,
            ])->values()->all(),
            'npcs' => $character->factionLoyalties->flatMap(fn ($loyalty) => $loyalty->factionLoyaltyNpcs)->map(fn ($npc) => [
                'npc_name' => $npc->npc?->name,
                'current_level' => $npc->current_level,
                'max_level' => $npc->max_level,
                'next_level_fame' => $npc->next_level_fame,
                'currently_helping' => $npc->currently_helping,
            ])->values()->all(),
            'automation_summary' => [
                'count' => $automations->count(),
                'latest_action' => $automations->first()?->last_automation_action,
                'latest_outcome' => $automations->first()?->last_fight_outcome,
            ],
        ];
    }
}
