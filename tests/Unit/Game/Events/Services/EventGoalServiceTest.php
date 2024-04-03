<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

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
                'reward_every'            => $eventGoal->reward_every,
                'amount_needed_for_reward' => 10,
                'current_kills'           => 0,
                'max_crafts'              => null,
                'current_crafts'          => 0,
            ],
            'status' => 200
        ];

        $this->assertEquals($expected, $this->eventGoalService->fetchCurrentEventGoal($character));
    }

    public function testFetchCurrentEventGoalData() {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

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
                'reward_every'            => $eventGoal->reward_every,
                'amount_needed_for_reward' => 10,
                'current_kills'           => 0,
                'max_crafts'              => null,
                'current_crafts'          => 0,
            ]
        ];

        $this->assertEquals($expected, $this->eventGoalService->getEventGoalData($character));
    }

    public function testFetchCurrentEventGoalDataWithCurrentKillCount() {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $eventGoal = $this->createGlobalEventGoal([
            'event_type'                      => EventType::WINTER_EVENT,
            'item_specialty_type_reward'      => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'                => true,
            'unique_type'                     => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'                => false,
        ]);

        $character->globalEventKills()->create([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $character->id,
            'kills'                => 10,
        ]);

        $character = $character->refresh();

        $expected = [
            'event_goals' => [
                'max_kills'               => $eventGoal->max_kills,
                'total_kills'             => $eventGoal->total_kills,
                'reward_every'            => $eventGoal->reward_every,
                'amount_needed_for_reward' => 10,
                'current_kills'           => 10,
                'max_crafts'              => null,
                'current_crafts'          => 0,
            ]
        ];

        $this->assertEquals($expected, $this->eventGoalService->getEventGoalData($character));
    }

    public function testFetchCurrentEventGoalKillRequiredIsEqualToRewardEvery() {
        $eventGoal = $this->createGlobalEventGoal([
            'event_type'                      => EventType::WINTER_EVENT,
            'item_specialty_type_reward'      => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'                => true,
            'unique_type'                     => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'                => false,
        ]);

        $this->assertEquals($eventGoal->reward_every, $this->eventGoalService->fetchAmountNeeded($eventGoal));
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

        $this->assertEquals(($eventGoal->reward_every / 2), $this->eventGoalService->fetchAmountNeeded($eventGoal));
    }
}
