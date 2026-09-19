<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Game\BattleRewardProcessing\Values\BattleRewardSharedContext;
use App\Game\Gems\Progression\Contracts\CharacterAreaGemEffects;

class BattleRewardSharedContextService
{
    /**
     * @param CharacterAreaGemEffects $characterAreaGemEffects
     * @param WeeklyBattleService $weeklyBattleService
     */
    public function __construct(
        private readonly CharacterAreaGemEffects $characterAreaGemEffects,
        private readonly WeeklyBattleService $weeklyBattleService,
    ) {}

    /**
     * Build the request-scoped shared reward context from an already-resolved Character and Monster.
     *
     * @param CharacterBattleRewardRequest $request
     * @param Character $character
     * @param Monster $monster
     * @return BattleRewardSharedContext
     */
    public function build(CharacterBattleRewardRequest $request, Character $character, Monster $monster): BattleRewardSharedContext
    {
        $payload = $request->handler_payload;
        $context = $payload['context'] ?? [];

        $resolvedAreaGemEffects = $this->characterAreaGemEffects->resolveForCharacterId($character->id);
        $locationSnapshot = $this->resolveLocationSnapshot($character);

        return new BattleRewardSharedContext(
            resolvedAreaGemEffects: $resolvedAreaGemEffects,
            sourceType: $request->source_type,
            killCount: $context['total_creatures'] ?? 1,
            isWeeklyMonster: $this->weeklyBattleService->isWeeklyMonster($monster),
            isGeneratedGemWorld: $character->map?->gameMap?->isGeneratedGemMap() ?? false,
            explorationLogId: $context['exploration_log_id'] ?? null,
            locationId: $locationSnapshot?->id,
            locationType: $locationSnapshot?->locationType(),
            locationName: $locationSnapshot?->name,
            locationX: $locationSnapshot?->x,
            locationY: $locationSnapshot?->y,
            locationGameMapId: $locationSnapshot?->game_map_id,
        );
    }

    /**
     * Resolve the Character's current Location, when one exists at their coordinates.
     *
     * @param Character $character
     * @return ?Location
     */
    private function resolveLocationSnapshot(Character $character): ?Location
    {
        return Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();
    }
}
