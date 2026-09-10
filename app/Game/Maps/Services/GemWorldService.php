<?php

namespace App\Game\Maps\Services;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Flare\Models\Character;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Location;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Maps\Transformers\GemWorldContextTransformer;

/**
 * Resolves the Player-facing Gem World Map card action, contextual Map/Location
 * Gem World entry eligibility, and Gem World entry/exit, delegating all Gem
 * effect calculation to AreaGemEffectService and all traversal to TraverseService.
 */
class GemWorldService
{
    use ChecksAutomationRestrictions, ResponseBuilder;

    public function __construct(
        private readonly TraverseService $traverseService,
        private readonly AreaGemEffectService $areaGemEffectService,
        private readonly GemWorldContextTransformer $gemWorldContextTransformer,
    ) {}

    /**
     * Resolve the current Gem World Map card state: whether the Character is inside a
     * generated Gem World, the current inspectable Gem context, the single contextually
     * valid entry, and the exit destination.
     */
    public function context(Character $character): array
    {
        $insideGemWorld = $character->map->gameMap->isGeneratedGemMap();

        return [
            'inside_gem_world' => $insideGemWorld,
            'current_context' => $this->resolveCurrentContext($character),
            'entry' => $insideGemWorld ? null : $this->resolveEntry($character),
            'exit' => $this->resolveExit($character),
        ];
    }

    /**
     * Enter the single contextually valid generated Gem World for the Character's current
     * persisted Map/Location state.
     */
    public function enter(Character $character): array
    {
        $restriction = $this->automationRestrictionErrorResult($character, AutomationRestrictionService::TRAVERSE);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if ($character->map->gameMap->isGeneratedGemMap()) {
            return $this->errorResult('You are already inside a Gem World.');
        }

        $entry = $this->resolveEntry($character);

        if (is_null($entry)) {
            return $this->errorResult('There is no Gem World available from your current Map or Location.');
        }

        $this->traverseService->travel($entry['generated_game_map']['id'], $character);

        return $this->successResult(['message' => 'You entered the Gem World.']);
    }

    /**
     * Exit the Character's current generated Gem World back to its authoritative parent Game Map.
     */
    public function exit(Character $character): array
    {
        $restriction = $this->automationRestrictionErrorResult($character, AutomationRestrictionService::TRAVERSE);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $gameMap = $character->map->gameMap;

        if (! $gameMap->isGeneratedGemMap()) {
            return $this->errorResult('You are not inside a Gem World.');
        }

        $parentMap = $gameMap->generatedParentMap;

        if (is_null($parentMap)) {
            return $this->errorResult('This Gem World does not have a valid parent Map.');
        }

        $this->traverseService->travel($parentMap->id, $character);

        return $this->successResult(['message' => 'You exited the Gem World.']);
    }

    /**
     * Resolve the Character's current factual Gem context, when any effect is present.
     */
    private function resolveCurrentContext(Character $character): ?array
    {
        $effects = $this->areaGemEffectService->resolveForCharacter($character);

        if (! $effects->hasAnyEffects()) {
            return null;
        }

        return $this->gemWorldContextTransformer->transform($effects);
    }

    /**
     * Resolve the Character's single contextually valid Gem World entry, when one exists.
     */
    private function resolveEntry(Character $character): ?array
    {
        $gameMap = $character->map->gameMap;

        if ($gameMap->isGeneratedGemMap()) {
            return null;
        }

        $location = $this->resolveCurrentLocation($character, $gameMap);

        if (! is_null($location)) {
            return $this->resolveLocationEntry($location, $gameMap);
        }

        return $this->resolveMapEntry($gameMap);
    }

    /**
     * Resolve the Location at the Character's exact current persisted coordinates.
     */
    private function resolveCurrentLocation(Character $character, GameMap $gameMap): ?Location
    {
        return Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $gameMap->id)
            ->first();
    }

    /**
     * Resolve the Location Gem World entry for the Character's current Location, when eligible.
     */
    private function resolveLocationEntry(Location $location, GameMap $currentMap): ?array
    {
        $location->loadMissing('gemParamters.rolledGem', 'gemParamters.generatedMap');

        $profile = $location->gemParamters;

        if (! $this->isEligibleLocationGemWorld($profile, $currentMap)) {
            return null;
        }

        $generatedMap = $profile->generatedMap;

        return [
            'type' => 'location_gem',
            'label' => 'Enter Location Gem',
            'generated_game_map' => ['id' => $generatedMap->id, 'name' => $generatedMap->name],
            'context' => $this->gemWorldContextTransformer->transform($this->areaGemEffectService->resolveForGameMap($generatedMap)),
        ];
    }

    /**
     * Resolve the Map Gem World entry for the Character's current normal Game Map, when eligible.
     */
    private function resolveMapEntry(GameMap $currentMap): ?array
    {
        $currentMap->loadMissing('gemParamters.rolledGem', 'gemParamters.generatedMap');

        $profile = $currentMap->gemParamters;

        if (! $this->isEligibleMapGemWorld($profile, $currentMap)) {
            return null;
        }

        $generatedMap = $profile->generatedMap;

        return [
            'type' => 'map_gem',
            'label' => 'Enter Map Gem',
            'generated_game_map' => ['id' => $generatedMap->id, 'name' => $generatedMap->name],
            'context' => $this->gemWorldContextTransformer->transform($this->areaGemEffectService->resolveForGameMap($generatedMap)),
        ];
    }

    /**
     * Determine whether the given Location Gem profile has a valid, correctly linked generated
     * Location Gem World for the Character's current normal Game Map.
     */
    private function isEligibleLocationGemWorld(?GameLocationGemParamter $profile, GameMap $currentMap): bool
    {
        if (is_null($profile) || is_null($profile->rolled_gem_id) || is_null($profile->rolledGem)) {
            return false;
        }

        $generatedMap = $profile->generatedMap;

        if (is_null($generatedMap)) {
            return false;
        }

        return $generatedMap->generated_map_type === GeneratedGemMapType::LOCATION_GEM->value
            && $generatedMap->game_location_gem_paramter_id === $profile->id
            && $generatedMap->generated_parent_game_map_id === $currentMap->id;
    }

    /**
     * Determine whether the given Map Gem profile has a valid, correctly linked generated
     * Map Gem World for the Character's current normal Game Map.
     */
    private function isEligibleMapGemWorld(?GameMapGemParamter $profile, GameMap $currentMap): bool
    {
        if (is_null($profile) || is_null($profile->rolled_gem_id) || is_null($profile->rolledGem)) {
            return false;
        }

        $generatedMap = $profile->generatedMap;

        if (is_null($generatedMap)) {
            return false;
        }

        return $generatedMap->generated_map_type === GeneratedGemMapType::MAP_GEM->value
            && $generatedMap->game_map_gem_paramter_id === $profile->id
            && $generatedMap->generated_parent_game_map_id === $currentMap->id;
    }

    /**
     * Resolve the Gem World exit destination for the Character's current generated Map, when inside one.
     */
    private function resolveExit(Character $character): ?array
    {
        $gameMap = $character->map->gameMap;

        if (! $gameMap->isGeneratedGemMap()) {
            return null;
        }

        $parentMap = $gameMap->generatedParentMap;

        if (is_null($parentMap)) {
            return null;
        }

        return [
            'label' => 'Exit Gem World',
            'game_map' => ['id' => $parentMap->id, 'name' => $parentMap->name],
        ];
    }
}
