<?php

namespace Tests\Setup\GemProgression;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Game\Battle\Services\BattleDrop;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\BattleRewardProcessing\Services\CharacterCurrencyRewardService;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Builders\BuildCosmicItem;
use App\Game\Core\Items\Builders\BuildMythicItem;
use App\Game\Core\Items\Builders\BuildUniqueItem;
use App\Game\Core\Items\Values\ItemSocketEligibility;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Progression\Services\GemProgressionBroadcastService;
use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use App\Game\Gems\Progression\Services\GemProgressionService;
use App\Game\Gems\Progression\Services\GemScrollEffectService;
use App\Game\Gems\Progression\Services\GemScrollGenerator;
use App\Game\Gems\Progression\Services\GemWorldProfileResolver;
use App\Game\Gems\Progression\Services\GemWorldRewardDeliveryService;
use App\Game\Gems\Progression\Services\GemWorldRewardPlanService;
use App\Game\Gems\Progression\Services\GemWorldRewardService;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateLocation;

/**
 * Domain setup factory for GemWorldRewardService tests: builds the real
 * subject under test and the generated Gem World Character fixture graphs
 * it needs, so test methods do not duplicate this large construction.
 */
class GemWorldRewardTestFactory
{
    use CreateGameLocationGemParamter, CreateGameMap, CreateGameMapGemParamter, CreateGem, CreateLocation;

    /**
     * Build the real GemWorldRewardService with real collaborators, optionally
     * replacing the ChanceCalculator with a controlled test double.
     */
    public function buildService(?ChanceCalculator $chanceCalculator = null): GemWorldRewardService
    {
        $planService = new GemWorldRewardPlanService(
            resolve(GemProgressionEffectService::class),
            resolve(GemScrollGenerator::class),
            resolve(RandomNumberGenerator::class),
            $chanceCalculator ?? resolve(ChanceCalculator::class),
        );

        $deliveryService = new GemWorldRewardDeliveryService(
            resolve(BattleRewardLedgerService::class),
            resolve(GemScrollGenerator::class),
            resolve(ItemSocketEligibility::class),
            resolve(GemBuilder::class),
            resolve(BuildUniqueItem::class),
            resolve(BuildMythicItem::class),
            resolve(BuildCosmicItem::class),
            resolve(BattleDrop::class),
        );

        return new GemWorldRewardService(
            resolve(GemWorldProfileResolver::class),
            resolve(GemProgressionService::class),
            resolve(GemScrollEffectService::class),
            $planService,
            $deliveryService,
            resolve(BattleRewardLedgerService::class),
            resolve(BattleRewardMessageOutboxService::class),
            resolve(CharacterCurrencyRewardService::class),
            resolve(GemProgressionBroadcastService::class),
        );
    }

    /**
     * Build a playable Character standing inside a generated Map Gem World
     * with a rolled Map Gem profile.
     */
    public function buildGeneratedMapGemWorldCharacter(): GemWorldRewardTestCharacterGraph
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $parentMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['gold_gain' => 0.05]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Gem World Reward Test Map '.uniqid(),
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $character->map->update(['game_map_id' => $generatedMap->id]);

        return new GemWorldRewardTestCharacterGraph($character->refresh(), $profile, null);
    }

    /**
     * Build a playable Character standing inside a generated Location Gem
     * World with a rolled Location Gem profile.
     */
    public function buildGeneratedLocationGemWorldCharacter(): GemWorldRewardTestCharacterGraph
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $parentMap = $character->map->gameMap;

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['gold_gain' => 0.05]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Gem World Reward Location Test '.uniqid(),
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationProfile->id,
        ]);

        $character->map->update(['game_map_id' => $generatedMap->id]);

        return new GemWorldRewardTestCharacterGraph($character->refresh(), null, $locationProfile);
    }
}
