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
            ['key' => 'highest_faction_level', 'label' => 'Highest Level Faction'],
            ['key' => 'total_faction_level', 'label' => 'Total Faction Level'],
            ['key' => 'npcs_helped_count', 'label' => 'NPCs Helped'],
            ['key' => 'total_npc_fame_level', 'label' => 'Total NPC Fame Level'],
        ];

        $rows = Character::with(['factions.gameMap', 'factionLoyalties.factionLoyaltyNpcs'])->get()->map(function (Character $character) {
            $npcs = $character->factionLoyalties->flatMap(fn ($loyalty) => $loyalty->factionLoyaltyNpcs);
            $highestFaction = $character->factions->sortByDesc('current_level')->first();

            return [
                'rank' => 0,
                'character_id' => $character->id,
                'character_name' => $character->name,
                'character_profile_url' => $this->characterProfileUrl($character->id),
                'highest_faction_level' => (int) $character->factions->max('current_level'),
                'highest_faction_name' => $highestFaction?->gameMap?->name,
                'highest_faction_points' => (int) $character->factions->max('current_points'),
                'total_faction_level' => (int) $character->factions->sum('current_level'),
                'maxed_faction_count' => $character->factions->where('maxed', true)->count(),
                'pledged_faction_count' => $character->factionLoyalties->where('is_pledged', true)->count(),
                'highest_npc_loyalty_level' => (int) $npcs->max('current_level'),
                'npcs_helped_count' => $npcs->where('current_level', '>', 1)->count(),
                'total_npc_fame_level' => (int) $npcs->sum('current_level'),
            ];
        })->filter(fn (array $row) => $row['highest_faction_level'] > 0 || $row['total_npc_fame_level'] > 0);

        // Chained sortByDesc calls are stable, so the LAST call is the primary sort
        // and each earlier call becomes a progressively deeper tie-break. Tie-break
        // priority: 1. total faction level, 2. NPCs helped, 3. total NPC fame level.
        $rows = $this->applySearch($rows, $parameters['search'] ?? null)
            ->sortByDesc('total_npc_fame_level')
            ->sortByDesc('npcs_helped_count')
            ->sortByDesc('total_faction_level')
            ->sortByDesc($metric)
            ->values()
            ->all();

        return $this->response('faction-loyalty', $metric, $period, $rows, $metrics, ['search' => $parameters['search'] ?? null]);
    }

    public function detail(Character $character): array
    {
        $character->load(['factions.gameMap', 'factionLoyalties.faction', 'factionLoyalties.factionLoyaltyNpcs.npc']);

        $automationQuery = FactionLoyaltyAutomation::query()->where('character_id', $character->id);
        $automationCount = (clone $automationQuery)->count();
        $latestAutomation = (clone $automationQuery)
            ->orderByDesc('last_automation_action_at')
            ->first(['last_automation_action', 'last_fight_outcome', 'last_automation_action_at']);

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
                'count' => $automationCount,
                'latest_action' => $latestAutomation?->last_automation_action,
                'latest_outcome' => $latestAutomation?->last_fight_outcome,
            ],
        ];
    }
}
