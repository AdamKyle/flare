<?php

namespace Tests\Unit\Game\Events\Concerns;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\BattleRewardProcessing\Handlers\BattleGlobalEventParticipationHandler;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGlobalEventGoal;

class UpdateCharacterEventGoalParticipationTest extends TestCase
{
    use CreateGlobalEventGoal, RefreshDatabase;

    private ?CharacterFactory $characterFactory;

    private ?BattleGlobalEventParticipationHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = new CharacterFactory;
        $this->handler = resolve(BattleGlobalEventParticipationHandler::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterFactory = null;
        $this->handler = null;
    }

    public function test_missing_kill_child_row_creates_kill_child_row_using_updated_parent_current_kills_total(): void
    {
        Event::fake();

        $character = $this->characterFactory->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'current_kills' => 5,
        ]);

        $this->handler->handleUpdatingParticipation($character->refresh(), $goal, 'kills', 1);

        $character = $character->refresh();

        $participation = $character->globalEventParticipation()
            ->where('global_event_goal_id', $goal->id)
            ->first();

        $this->assertEquals(6, $participation->current_kills);

        $killsRow = $character->globalEventKills()
            ->where('global_event_goal_id', $goal->id)
            ->first();

        $this->assertNotNull($killsRow);
        $this->assertEquals(6, $killsRow->kills);
    }

    public function test_missing_craft_child_row_creates_craft_child_row_using_updated_parent_current_crafts_total(): void
    {
        Event::fake();

        $character = $this->characterFactory->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'max_crafts' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'current_crafts' => 3,
        ]);

        $this->handler->handleUpdatingParticipation($character->refresh(), $goal, 'crafts', 2);

        $character = $character->refresh();

        $participation = $character->globalEventParticipation()
            ->where('global_event_goal_id', $goal->id)
            ->first();

        $this->assertEquals(5, $participation->current_crafts);

        $craftsRow = $character->globalEventCrafts()
            ->where('global_event_goal_id', $goal->id)
            ->first();

        $this->assertNotNull($craftsRow);
        $this->assertEquals(5, $craftsRow->crafts);
    }

    public function test_missing_enchant_child_row_creates_enchant_child_row_using_updated_parent_current_enchants_total(): void
    {
        Event::fake();

        $character = $this->characterFactory->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'max_enchants' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'current_enchants' => 7,
        ]);

        $this->handler->handleUpdatingParticipation($character->refresh(), $goal, 'enchants', 3);

        $character = $character->refresh();

        $participation = $character->globalEventParticipation()
            ->where('global_event_goal_id', $goal->id)
            ->first();

        $this->assertEquals(10, $participation->current_enchants);

        $enchantsRow = $character->globalEventEnchants()
            ->where('global_event_goal_id', $goal->id)
            ->first();

        $this->assertNotNull($enchantsRow);
        $this->assertEquals(10, $enchantsRow->enchants);
    }

    public function test_existing_child_row_is_updated_to_the_same_total_as_the_parent_participation_row(): void
    {
        Event::fake();

        $character = $this->characterFactory->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'current_kills' => 8,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'kills' => 8,
        ]);

        $this->handler->handleUpdatingParticipation($character->refresh(), $goal, 'kills', 4);

        $character = $character->refresh();

        $participation = $character->globalEventParticipation()
            ->where('global_event_goal_id', $goal->id)
            ->first();

        $killsRow = $character->globalEventKills()
            ->where('global_event_goal_id', $goal->id)
            ->first();

        $this->assertEquals(12, $participation->current_kills);
        $this->assertEquals($participation->current_kills, $killsRow->kills);
    }

    public function test_different_global_event_goal_id_rows_are_not_touched(): void
    {
        Event::fake();

        $character = $this->characterFactory->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $goal1 = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $goal2 = $this->createGlobalEventGoal([
            'max_kills' => 200,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal2->id,
            'character_id' => $character->id,
            'current_kills' => 10,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $goal2->id,
            'character_id' => $character->id,
            'kills' => 10,
        ]);

        $this->handler->handleUpdatingParticipation($character->refresh(), $goal1, 'kills', 5);

        $character = $character->refresh();

        $participationForGoal2 = $character->globalEventParticipation()
            ->where('global_event_goal_id', $goal2->id)
            ->first();

        $killsForGoal2 = $character->globalEventKills()
            ->where('global_event_goal_id', $goal2->id)
            ->first();

        $this->assertEquals(10, $participationForGoal2->current_kills);
        $this->assertEquals(10, $killsForGoal2->kills);

        $participationForGoal1 = $character->globalEventParticipation()
            ->where('global_event_goal_id', $goal1->id)
            ->first();

        $this->assertNotNull($participationForGoal1);
        $this->assertEquals(5, $participationForGoal1->current_kills);
    }
}
