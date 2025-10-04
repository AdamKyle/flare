<?php

namespace Tests\Feature\Game\Events\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGlobalEventGoal;

class EventGoalsControllerTest extends TestCase
{
    use CreateGlobalEventGoal, RefreshDatabase;

    private ?Character $character = null;

    private ?GlobalEventGoal $eventGoal = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->eventGoal = $this->createGlobalEventGoal([
            'max_kills' => 1000,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $this->eventGoal->id,
            'character_id' => $this->character->id,
            'kills' => 10,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $this->eventGoal->id,
            'character_id' => $this->character->id,
            'current_kills' => 10,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->eventGoal = null;
    }

    public function test_get_global_event_goal()
    {

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/global-event-goals/'.$this->character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals([
            'max_kills' => $this->eventGoal->max_kills,
            'total_kills' => $this->eventGoal->total_kills,
            'reward_every' => $this->eventGoal->reward_every,
            'amount_needed_for_reward' => 100,
            'current_kills' => 10,
            'max_crafts' => null,
            'max_enchants' => null,
            'current_crafts' => 0,
            'current_enchants' => 0,
            'should_be_mythic' => false,
            'should_be_unique' => true,
            'reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'total_crafts' => 0,
            'total_enchants' => 0,
        ], $jsonData['event_goals']);
    }
}
