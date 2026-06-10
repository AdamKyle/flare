<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalEventGoal;

class EventGoalsServiceTest extends TestCase
{
    use CreateGameMap, CreateGlobalEventGoal, RefreshDatabase;

    private ?EventGoalsService $eventGoalsService;

    public function setUp(): void
    {
        parent::setUp();

        $this->eventGoalsService = resolve(EventGoalsService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->eventGoalsService = null;
    }

    public function testReturnsGoalForCharacterCurrentEventMap(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $goal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertNotNull($result['event_goals']);
        $this->assertEquals($goal->max_kills, $result['event_goals']['max_kills']);
        $this->assertEquals(ItemSpecialtyType::CORRUPTED_ICE, $result['event_goals']['reward']);
    }

    public function testReadsCurrentKillsCraftsEnchantsFromRowsMatchingActiveGoalId(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $goal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $otherGoal = $this->createGlobalEventGoal([
            'max_kills' => 50,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $character->globalEventKills()->create([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'kills' => 7,
        ]);

        $character->globalEventKills()->create([
            'global_event_goal_id' => $otherGoal->id,
            'character_id' => $character->id,
            'kills' => 99,
        ]);

        $character = $character->refresh();

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertEquals(7, $result['event_goals']['current_kills']);
    }

    public function testPayloadShowsUpdatedCurrentCraftsOnWinterMap(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $goal = $this->createGlobalEventGoal([
            'max_crafts' => 50,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $character->globalEventCrafts()->create([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'crafts' => 5,
        ]);

        $character = $character->refresh();

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertEquals(5, $result['event_goals']['current_crafts']);
    }

    public function testPayloadShowsUpdatedCurrentEnchantsOnWinterMap(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $goal = $this->createGlobalEventGoal([
            'max_enchants' => 50,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $character->globalEventEnchants()->create([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'enchants' => 3,
        ]);

        $character = $character->refresh();

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertEquals(3, $result['event_goals']['current_enchants']);
    }

    public function testPayloadShowsUpdatedCurrentKillsOnWinterMap(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $goal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $character->globalEventKills()->create([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'kills' => 15,
        ]);

        $character = $character->refresh();

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertEquals(15, $result['event_goals']['current_kills']);
    }

    public function testDelusionalMemoriesMapShowsUpdatedKillsWhenBattleStepActive(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::DELUSIONAL_MEMORIES,
                'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $goal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $character->globalEventKills()->create([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'kills' => 22,
        ]);

        $character = $character->refresh();

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertNotNull($result['event_goals']);
        $this->assertEquals(22, $result['event_goals']['current_kills']);
    }
}
