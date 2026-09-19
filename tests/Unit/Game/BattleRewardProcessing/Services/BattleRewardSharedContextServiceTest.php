<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Services\BattleRewardSharedContextService;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class BattleRewardSharedContextServiceTest extends TestCase
{
    use CreateCharacterBattleReward, CreateGameMap, CreateLocation, CreateMonster, RefreshDatabase;

    public function test_manual_battle_kill_count_normalizes_to_one(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $context = resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster);

        $this->assertSame(1, $context->killCount());
    }

    public function test_aggregate_total_creatures_is_retained(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::EXPLORATION,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => ['total_creatures' => 7]],
        ]);

        $context = resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster);

        $this->assertSame(7, $context->killCount());
    }

    public function test_exploration_log_id_is_retained(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::EXPLORATION,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => ['exploration_log_id' => 99]],
        ]);

        $context = resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster);

        $this->assertSame(99, $context->explorationLogId());
    }

    public function test_current_location_identity_is_resolved_from_the_characters_coordinates(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = $this->createLocation(['x' => 16, 'y' => 16, 'game_map_id' => $character->map->game_map_id, 'type' => LocationType::GOLD_MINES->value]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $context = resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster);

        $this->assertSame($location->id, $context->locationId());
        $this->assertTrue($context->locationType()->isGoldMines());
    }

    public function test_no_location_at_the_characters_coordinates_resolves_to_null(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $context = resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster);

        $this->assertNull($context->locationId());
        $this->assertNull($context->locationType());
    }

    public function test_weekly_monster_boolean_matches_weekly_battle_service(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $context = resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster);

        $this->assertTrue($context->isWeeklyMonster());
    }

    public function test_non_weekly_monster_boolean_is_false(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'only_for_location_type' => null]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $context = resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster);

        $this->assertFalse($context->isWeeklyMonster());
    }

    public function test_generated_gem_world_boolean_is_true_on_a_generated_map(): void
    {
        $gemWorldMap = $this->createGameMap([
            'name' => 'Shared Context Gem World Test',
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gemWorldMap)->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $gemWorldMap->id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $context = resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster);

        $this->assertTrue($context->isGeneratedGemWorld());
    }

    public function test_generated_gem_world_boolean_is_false_on_an_ordinary_map(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        $context = resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster);

        $this->assertFalse($context->isGeneratedGemWorld());
    }
}
