<?php

namespace Tests\Feature\Game\Maps;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;
use App\Flare\Values\MapNameValue;
use App\Game\Maps\Services\WalkingService;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

/**
 * This is a feature / integration test around walking movement while automation is running.
 */
class WalkingServiceAutomationRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::forget('celestial-spawn-rate');
        Cache::forget('monsters');
        Mockery::close();

        parent::tearDown();
    }

    public function test_exploration_started_in_gold_mine_blocks_directional_movement(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();

        Location::factory()->create([
            'name' => 'Gold Mine',
            'game_map_id' => $gameMap->id,
            'x' => 16,
            'y' => 16,
            'type' => LocationType::GOLD_MINES,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => null,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => true,
        ]);

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals(16, $character->refresh()->map->character_position_x);
        $this->assertEquals(16, $character->refresh()->map->character_position_y);
    }

    public function test_exploration_started_in_purgatory_dungeon_blocks_directional_movement(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();

        Location::factory()->create([
            'name' => 'Purgatory Dungeon',
            'game_map_id' => $gameMap->id,
            'x' => 16,
            'y' => 16,
            'type' => LocationType::PURGATORY_DUNGEONS,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => null,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => true,
        ]);

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals(16, $character->refresh()->map->character_position_x);
        $this->assertEquals(16, $character->refresh()->map->character_position_y);
    }

    public function test_exploration_started_in_purgatory_smith_house_blocks_directional_movement(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();

        Location::factory()->create([
            'name' => 'Purgatory Smith House',
            'game_map_id' => $gameMap->id,
            'x' => 16,
            'y' => 16,
            'type' => LocationType::PURGATORY_SMITH_HOUSE,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => null,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => true,
        ]);

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals(16, $character->refresh()->map->character_position_x);
        $this->assertEquals(16, $character->refresh()->map->character_position_y);
    }

    public function test_exploration_started_in_regular_context_allows_directional_movement_to_regular_location(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapNameValue::SURFACE => []]);
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => null,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        Location::factory()->create([
            'name' => 'Regular',
            'game_map_id' => $gameMap->id,
            'x' => 32,
            'y' => 16,
            'type' => null,
            'enemy_strength_type' => null,
            'is_port' => false,
        ]);

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals(32, $character->refresh()->map->character_position_x);
        $this->assertEquals(16, $character->refresh()->map->character_position_y);
    }

    public function test_exploration_started_in_regular_context_allows_directional_movement_to_port(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapNameValue::SURFACE => []]);
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => null,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        Location::factory()->create([
            'name' => 'Port',
            'game_map_id' => $gameMap->id,
            'x' => 32,
            'y' => 16,
            'type' => null,
            'enemy_strength_type' => null,
            'is_port' => true,
        ]);

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals(32, $character->refresh()->map->character_position_x);
        $this->assertEquals(16, $character->refresh()->map->character_position_y);
    }

    public function test_exploration_started_in_regular_context_blocks_entry_into_gold_mine(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => null,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        Location::factory()->create([
            'name' => 'Gold Mine',
            'game_map_id' => $gameMap->id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::GOLD_MINES,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals(16, $character->refresh()->map->character_position_x);
        $this->assertEquals(16, $character->refresh()->map->character_position_y);
    }

    public function test_exploration_started_in_regular_context_blocks_entry_into_purgatory_dungeon(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => null,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        Location::factory()->create([
            'name' => 'Purgatory Dungeon',
            'game_map_id' => $gameMap->id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::PURGATORY_DUNGEONS,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals(16, $character->refresh()->map->character_position_x);
        $this->assertEquals(16, $character->refresh()->map->character_position_y);
    }

    public function test_exploration_started_in_regular_context_blocks_entry_into_purgatory_smith_house(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => null,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        Location::factory()->create([
            'name' => 'Purgatory Smith House',
            'game_map_id' => $gameMap->id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::PURGATORY_SMITH_HOUSE,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals(16, $character->refresh()->map->character_position_x);
        $this->assertEquals(16, $character->refresh()->map->character_position_y);
    }

    public function test_blocked_special_location_entry_does_not_delete_current_exploration_automation(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => null,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        Location::factory()->create([
            'name' => 'Gold Mine',
            'game_map_id' => $gameMap->id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::GOLD_MINES,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);
        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(422, $response['status']);
        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_blocked_special_location_entry_does_not_delete_unrelated_automation(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();
        $unrelatedCharacter = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => null,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        $unrelatedAutomation = CharacterAutomation::factory()->create([
            'character_id' => $unrelatedCharacter->id,
            'monster_id' => null,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $unrelatedCharacter->level,
            'current_level' => $unrelatedCharacter->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        Location::factory()->create([
            'name' => 'Gold Mine',
            'game_map_id' => $gameMap->id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::GOLD_MINES,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);
        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(422, $response['status']);
        $this->assertNotNull(CharacterAutomation::find($unrelatedAutomation->id));
    }

    public function test_allowed_regular_movement_does_not_corrupt_active_exploration_automation(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapNameValue::SURFACE => []]);
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'monster_id' => null,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        Location::factory()->create([
            'name' => 'Regular',
            'game_map_id' => $gameMap->id,
            'x' => 32,
            'y' => 16,
            'type' => null,
            'enemy_strength_type' => null,
            'is_port' => false,
        ]);
        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);
        $persistedAutomation = CharacterAutomation::find($automation->id);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals(AutomationType::EXPLORING, $persistedAutomation->type);
        $this->assertFalse($persistedAutomation->started_in_special_location);
        $this->assertEquals($character->level, $persistedAutomation->current_level);
    }
}
