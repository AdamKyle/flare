<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Services\BattleRewardSharedContextService;
use App\Game\BattleRewardProcessing\Services\BattleRewardStepPlanService;
use App\Game\Events\Values\EventType;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateScheduledEvent;

class BattleRewardStepPlanServiceTest extends TestCase
{
    use CreateCharacterBattleReward, CreateGameMap, CreateLocation, CreateMonster, CreateScheduledEvent, RefreshDatabase;

    public function test_normal_battle_plan_excludes_build_reward_plan_and_exploration_context(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertFalse(in_array(BattleRewardStepName::BUILD_REWARD_PLAN, $steps, true));
        $this->assertFalse(in_array(BattleRewardStepName::EXPLORATION_CONTEXT, $steps, true));
    }

    public function test_exploration_plan_includes_build_reward_plan_and_exploration_context(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::EXPLORATION,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => ['exploration_log_id' => 42]],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertTrue(in_array(BattleRewardStepName::BUILD_REWARD_PLAN, $steps, true));
        $this->assertTrue(in_array(BattleRewardStepName::EXPLORATION_CONTEXT, $steps, true));
    }

    public function test_non_weekly_monster_plan_excludes_weekly_rewards(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'only_for_location_type' => null]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertFalse(in_array(BattleRewardStepName::WEEKLY_REWARDS, $steps, true));
    }

    public function test_weekly_monster_plan_includes_weekly_rewards(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertTrue(in_array(BattleRewardStepName::WEEKLY_REWARDS, $steps, true));
    }

    public function test_ordinary_map_plan_excludes_gem_world_rewards(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertFalse(in_array(BattleRewardStepName::GEM_WORLD_REWARDS, $steps, true));
    }

    public function test_generated_gem_world_plan_includes_gem_world_rewards(): void
    {
        $gemWorldMap = $this->createGameMap([
            'name' => 'Generated Gem World Test',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gemWorldMap)->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $gemWorldMap->id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertTrue(in_array(BattleRewardStepName::GEM_WORLD_REWARDS, $steps, true));
    }

    public function test_no_special_location_plan_excludes_specific_location_rewards(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertFalse(in_array(BattleRewardStepName::SPECIFIC_LOCATION_REWARDS, $steps, true));
    }

    public function test_gold_mines_location_plan_includes_specific_location_rewards(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createLocation(['x' => 16, 'y' => 16, 'game_map_id' => $character->map->game_map_id, 'type' => LocationType::GOLD_MINES->value]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertTrue(in_array(BattleRewardStepName::SPECIFIC_LOCATION_REWARDS, $steps, true));
    }

    public function test_purgatory_smith_house_location_plan_includes_specific_location_rewards(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createLocation(['x' => 16, 'y' => 16, 'game_map_id' => $character->map->game_map_id, 'type' => LocationType::PURGATORY_SMITH_HOUSE->value]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertTrue(in_array(BattleRewardStepName::SPECIFIC_LOCATION_REWARDS, $steps, true));
    }

    public function test_the_old_church_location_plan_includes_specific_location_rewards(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createLocation(['x' => 16, 'y' => 16, 'game_map_id' => $character->map->game_map_id, 'type' => LocationType::THE_OLD_CHURCH->value]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertTrue(in_array(BattleRewardStepName::SPECIFIC_LOCATION_REWARDS, $steps, true));
    }

    public function test_no_active_winter_event_plan_excludes_winter_event(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertFalse(in_array(BattleRewardStepName::WINTER_EVENT, $steps, true));
    }

    public function test_active_winter_event_off_ice_plane_plan_excludes_winter_event(): void
    {
        $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'currently_running' => true]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertFalse(in_array(BattleRewardStepName::WINTER_EVENT, $steps, true));
    }

    public function test_active_winter_event_on_ice_plane_plan_includes_winter_event(): void
    {
        $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'currently_running' => true]);
        $iceMap = $this->createGameMap(['name' => 'The Ice Plane']);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $iceMap)->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $iceMap->id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $steps = resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster))->steps();

        $this->assertTrue(in_array(BattleRewardStepName::WINTER_EVENT, $steps, true));
    }

    public function test_plan_faction_loyalty_returns_the_fixed_faction_loyalty_step_plan(): void
    {
        $steps = resolve(BattleRewardStepPlanService::class)->planFactionLoyalty()->steps();

        $this->assertSame(BattleRewardStepName::orderedForFactionLoyalty(), $steps);
    }

    public function test_plan_quest_returns_the_fixed_quest_step_plan(): void
    {
        $steps = resolve(BattleRewardStepPlanService::class)->planQuest()->steps();

        $this->assertSame(BattleRewardStepName::orderedForQuest(), $steps);
    }
}
