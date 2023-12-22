<?php

namespace Tests\Console\Events;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Values\RandomAffixDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGlobalEventGoal;

class RestartGlobalEventGoalTest extends TestCase {
    use RefreshDatabase, CreateGlobalEventGoal;

    private $eventGoal;

    private Character $character;

    public function setUp(): void {

        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        GlobalEventGoal::truncate();
        GlobalEventKill::truncate();
        GlobalEventParticipation::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->eventGoal = $this->createGlobalEventGoal([
            'max_kills'                      => 1000,
            'reward_every_kills'             => 100,
            'next_reward_at'                 => 100,
            'event_type'                     => EventType::WINTER_EVENT,
            'item_specialty_type_reward'     => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique'               => true,
            'unique_type'                    => RandomAffixDetails::LEGENDARY,
            'should_be_mythic'               => false,
        ]);
    }

    public function tearDown(): void {

        parent::tearDown();

        $this->eventGoal = null;
    }

    public function testRestEventGoal() {

        $this->createGlobalEventKill([
            'global_event_goal_id'  => $this->eventGoal->id,
            'character_id'          => $this->character->id,
            'kills'                 => 1000,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $this->eventGoal->id,
            'character_id'         => $this->character->id,
            'current_kills'        => 1000,
        ]);

        $this->artisan('restart:global-event-goal');

        $this->eventGoal = $this->eventGoal->refresh();

        $this->assertEmpty($this->eventGoal->globalEventParticipation);
        $this->assertEmpty($this->eventGoal->globalEventKills);
    }

    public function testDoNotRestEventGoal() {

        $this->createGlobalEventKill([
            'global_event_goal_id'  => $this->eventGoal->id,
            'character_id'          => $this->character->id,
            'kills'                 => 10,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $this->eventGoal->id,
            'character_id'         => $this->character->id,
            'current_kills'        => 10,
        ]);

        // dd(GlobalEventKill::count());

        $this->artisan('restart:global-event-goal');

        $this->eventGoal = $this->eventGoal->refresh();

        $this->assertNotEmpty($this->eventGoal->globalEventParticipation);
        $this->assertNotEmpty($this->eventGoal->globalEventKills);
    }
}
