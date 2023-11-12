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

class EventGoalServiceTest extends TestCase {

    use RefreshDatabase, CreateGlobalEventGoal;

    private ?EventGoalsService $eventGoalService;

    public function setUp(): void {
        parent::setUp();

        $this->eventGoalService = resolve(EventGoalsService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->eventGoalService = null;
    }

    public function testFetchCurrentEventGoalDataForResponse() {
        $eventGoal = $this->createGlobalEventGoal([
            'event_type'                      => EventType::WINTER_EVENT,
            'item_specialty_type_reward'      => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'                => true,
            'unique_type'                     => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'                => false,
        ]);

        $expected = [
            'event_goals' => [
                'max_kills'               => $eventGoal->max_kills,
                'total_kills'             => $eventGoal->total_kills,
                'reward_every'            => $eventGoal->reward_every_kills,
                'kills_needed_for_reward' => 10,
            ],
            'status' => 200
        ];

        $this->assertEquals($expected, $this->eventGoalService->fetchCurrentEventGoal());
    }

    public function testFetchCurrentEventGoalData() {
        $eventGoal = $this->createGlobalEventGoal([
            'event_type'                      => EventType::WINTER_EVENT,
            'item_specialty_type_reward'      => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'                => true,
            'unique_type'                     => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'                => false,
        ]);

        $expected = [
            'event_goals' => [
                'max_kills'               => $eventGoal->max_kills,
                'total_kills'             => $eventGoal->total_kills,
                'reward_every'            => $eventGoal->reward_every_kills,
                'kills_needed_for_reward' => 10,
            ]
        ];

        $this->assertEquals($expected, $this->eventGoalService->getEventGoalData());
    }

    public function testFetchCurrentEventGoalKillRequiredIsEqualToRewardEvery() {
        $eventGoal = $this->createGlobalEventGoal([
            'event_type'                      => EventType::WINTER_EVENT,
            'item_specialty_type_reward'      => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'                => true,
            'unique_type'                     => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'                => false,
        ]);

        $this->assertEquals($eventGoal->reward_every_kills, $this->eventGoalService->fetchKillAmountNeeded($eventGoal));
    }

    public function testOnlyNeedsHalfOfRewardEveryAsCurrentKillCount() {
        $eventGoal = $this->createGlobalEventGoal([
            'event_type'                      => EventType::WINTER_EVENT,
            'item_specialty_type_reward'      => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'                => true,
            'unique_type'                     => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'                => false,
        ]);

        $eventGoal->globalEventParticipation()->create([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => (new CharacterFactory())->createBaseCharacter()->getCharacter()->id,
            'current_kills' => 10,
        ]);

        $eventGoal->globalEventParticipation()->create([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => (new CharacterFactory())->createBaseCharacter()->getCharacter()->id,
            'current_kills' => 14,
        ]);

        $this->assertEquals(($eventGoal->reward_every_kills / 2), $this->eventGoalService->fetchKillAmountNeeded($eventGoal));
    }
}
