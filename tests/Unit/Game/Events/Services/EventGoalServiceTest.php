<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGlobalEventGoal;

class EventGoalServiceTest extends TestCase
{
    use CreateGlobalEventGoal, RefreshDatabase;

    private ?EventGoalsService $eventGoalService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventGoalService = resolve(EventGoalsService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->eventGoalService = null;
    }

    public function test_fetch_current_event_goal_data_for_response()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $eventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $expected = [
            'event_goals' => [
                'max_kills' => $eventGoal->max_kills,
                'total_kills' => $eventGoal->total_kills,
                'reward_every' => $eventGoal->reward_every,
                'amount_needed_for_reward' => 10,
                'current_kills' => 0,
                'max_crafts' => null,
                'current_crafts' => 0,
                'current_enchants' => 0,
                'total_crafts' => 0,
                'total_enchants' => 0,
                'max_enchants' => null,
                'should_be_mythic' => false,
                'should_be_unique' => true,
                'reward' => ItemSpecialtyType::CORRUPTED_ICE,
            ],
            'status' => 200,
        ];

        $this->assertEquals($expected, $this->eventGoalService->fetchCurrentEventGoal($character));
    }

    public function test_fetch_current_event_goal_data()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $eventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $expected = [
            'event_goals' => [
                'max_kills' => $eventGoal->max_kills,
                'total_kills' => $eventGoal->total_kills,
                'reward_every' => $eventGoal->reward_every,
                'amount_needed_for_reward' => 10,
                'current_kills' => 0,
                'max_crafts' => null,
                'current_crafts' => 0,
                'current_enchants' => 0,
                'total_crafts' => 0,
                'total_enchants' => 0,
                'max_enchants' => null,
                'should_be_mythic' => false,
                'should_be_unique' => true,
                'reward' => ItemSpecialtyType::CORRUPTED_ICE,
            ],
        ];

        $this->assertEquals($expected, $this->eventGoalService->getEventGoalData($character));
    }

    public function test_fetch_current_event_goal_data_with_current_kill_count()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $eventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $character->globalEventKills()->create([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'kills' => 10,
        ]);

        $character = $character->refresh();

        $expected = [
            'event_goals' => [
                'max_kills' => $eventGoal->max_kills,
                'total_kills' => $eventGoal->total_kills,
                'reward_every' => $eventGoal->reward_every,
                'amount_needed_for_reward' => 10,
                'current_kills' => 10,
                'max_crafts' => null,
                'current_crafts' => 0,
                'current_enchants' => 0,
                'total_crafts' => 0,
                'total_enchants' => 0,
                'max_enchants' => null,
                'should_be_mythic' => false,
                'should_be_unique' => true,
                'reward' => ItemSpecialtyType::CORRUPTED_ICE,
            ],
        ];

        $this->assertEquals($expected, $this->eventGoalService->getEventGoalData($character));
    }

    public function test_fetch_current_event_goal_kill_required_is_equal_to_reward_every()
    {
        $eventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->assertEquals($eventGoal->reward_every, $this->eventGoalService->fetchAmountNeeded($eventGoal));
    }

    public function test_only_needs_half_of_reward_every_as_current_kill_count()
    {
        $eventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $eventGoal->globalEventParticipation()->create([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => (new CharacterFactory)->createBaseCharacter()->getCharacter()->id,
            'current_kills' => 10,
        ]);

        $eventGoal->globalEventParticipation()->create([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => (new CharacterFactory)->createBaseCharacter()->getCharacter()->id,
            'current_kills' => 14,
        ]);

        $this->assertEquals(($eventGoal->reward_every / 2), $this->eventGoalService->fetchAmountNeeded($eventGoal));
    }
}
