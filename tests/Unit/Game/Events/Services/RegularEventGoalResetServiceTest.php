<?php

namespace Tests\Unit\Game\Events\Services;

use App\Game\Events\Services\RegularEventGoalResetService;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Setup\Character\CharacterFactory;

class RegularEventGoalResetServiceTest extends TestCase
{
    use RefreshDatabase, CreateGlobalEventGoal;

    private ?RegularEventGoalResetService $service = null;

    private $character;

    public function setUp(): void
    {
        parent::setUp();

        $this->service   = new RegularEventGoalResetService();
        $this->character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
    }

    public function tearDown(): void
    {
        $this->service   = null;
        $this->character = null;

        parent::tearDown();
    }

    public function testResetsNextRewardAndClearsParticipationAndKillsForOnlyTheGivenGoal(): void
    {
        $goal = $this->createGlobalEventGoal([
            'event_type'                 => EventType::DELUSIONAL_MEMORIES_EVENT,
            'reward_every'               => 100,
            'next_reward_at'             => 999, // will be reset to reward_every
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'           => true,
            'unique_type'                => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'           => false,
            'max_kills'                  => 1000,
        ]);

        $otherGoal = $this->createGlobalEventGoal([
            'event_type'                 => EventType::DELUSIONAL_MEMORIES_EVENT,
            'reward_every'               => 50,
            'next_reward_at'             => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'           => true,
            'unique_type'                => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'           => false,
            'max_kills'                  => 10,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id'         => $this->character->id,
            'current_kills'        => 50,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $goal->id,
            'character_id'         => $this->character->id,
            'kills'                => 50,
        ]);

        // Control rows linked to a different goal should remain after reset
        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $otherGoal->id,
            'character_id'         => $this->character->id,
            'current_kills'        => 7,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $otherGoal->id,
            'character_id'         => $this->character->id,
            'kills'                => 7,
        ]);

        $this->service->reset($goal->fresh());

        $goal = $goal->refresh();

        $this->assertEquals(100, $goal->next_reward_at);

        $this->assertCount(0, GlobalEventParticipation::where('global_event_goal_id', $goal->id)->get());
        $this->assertCount(0, GlobalEventKill::where('global_event_goal_id', $goal->id)->get());

        $this->assertCount(1, GlobalEventParticipation::where('global_event_goal_id', $otherGoal->id)->get());
        $this->assertCount(1, GlobalEventKill::where('global_event_goal_id', $otherGoal->id)->get());
    }
}
