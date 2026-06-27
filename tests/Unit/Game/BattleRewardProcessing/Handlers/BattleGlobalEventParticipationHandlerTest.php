<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Handlers;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\BattleRewardProcessing\Handlers\BattleGlobalEventParticipationHandler;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGlobalEventGoal;

class BattleGlobalEventParticipationHandlerTest extends TestCase
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

    public function test_creates_participation_on_first_kill(): void
    {
        Event::fake();

        $character = $this->characterFactory->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $globalEventGoal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->handler->handleGlobalEventParticipation($character, $globalEventGoal, 1);

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventParticipation);
        $this->assertEquals(1, $character->globalEventParticipation->current_kills);

        $killsRow = $character->globalEventKills()
            ->where('global_event_goal_id', $globalEventGoal->id)
            ->first();

        $this->assertNotNull($killsRow);
        $this->assertEquals(1, $killsRow->kills);
    }

    public function test_updates_existing_participation_scoped_by_goal_id(): void
    {
        Event::fake();

        $character = $this->characterFactory->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $globalEventGoal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $globalEventGoal->id,
            'character_id' => $character->id,
            'current_kills' => 5,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $globalEventGoal->id,
            'character_id' => $character->id,
            'kills' => 5,
        ]);

        $this->handler->handleGlobalEventParticipation($character->refresh(), $globalEventGoal, 3);

        $character = $character->refresh();

        $this->assertEquals(8, $character->globalEventParticipation->current_kills);

        $killsRow = $character->globalEventKills()
            ->where('global_event_goal_id', $globalEventGoal->id)
            ->first();

        $this->assertEquals(8, $killsRow->kills);
    }

    public function test_kills_are_capped_at_max_kills(): void
    {
        Event::fake();

        $character = $this->characterFactory->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $globalEventGoal = $this->createGlobalEventGoal([
            'max_kills' => 10,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $globalEventGoal->id,
            'character_id' => $character->id,
            'current_kills' => 8,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $globalEventGoal->id,
            'character_id' => $character->id,
            'kills' => 8,
        ]);

        $this->handler->handleGlobalEventParticipation($character->refresh(), $globalEventGoal, 5);

        $character = $character->refresh();

        $this->assertEquals(10, $character->globalEventParticipation->current_kills);

        $killsRow = $character->globalEventKills()
            ->where('global_event_goal_id', $globalEventGoal->id)
            ->first();

        $this->assertEquals(10, $killsRow->kills);
    }
}
