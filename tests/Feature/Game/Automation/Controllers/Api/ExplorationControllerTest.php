<?php

namespace Tests\Feature\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Monster;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Models\Location;
use App\Flare\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;

class ExplorationControllerTest extends TestCase
{
    use RefreshDatabase;

    private Character $character;

    private Monster $monster;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $this->character->map->game_map_id,
            ])
            ->getMonster();
    }

    public function testBeginStartsExploration(): void
    {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'auto_attack_length' => 1,
                'move_down_the_list_every' => 10,
                'selected_monster_id' => $this->monster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(
            'Exploration has started. Check the exploration tab (beside server messages) for update. The tab will every 1 minutes, rewards are handed to you or disenchanted automatically.',
            $jsonData['message']
        );
    }

    public function testStopStopsExploration(): void
    {
        Event::fake();

        CharacterAutomation::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/automation/' . $this->character->id . '/stop', [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBeginReturns422WhenAttackTypeIsInvalid(): void
    {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'auto_attack_length' => 1,
                'move_down_the_list_every' => 10,
                'selected_monster_id' => $this->monster->id,
                'attack_type' => 'invalid',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Invalid attack type was selected. Please select from the drop down.', $jsonData['message']);
    }

    public function testBeginReturns422WhenAutomationIsAlreadyRunning(): void
    {
        Queue::fake();
        Event::fake();

        CharacterAutomation::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'auto_attack_length' => 1,
                'move_down_the_list_every' => 10,
                'selected_monster_id' => $this->monster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You cannot do that while Exploration automation is running. Cancel it first.', $jsonData['message']);
    }

    public function testBeginReturns422WhenCharacterIsOnBlockedLocation(): void
    {
        Queue::fake();
        Event::fake();

        Location::factory()->create([
            'x' => $this->character->map->character_position_x,
            'y' => $this->character->map->character_position_y,
            'game_map_id' => $this->character->map->game_map_id,
            'type' => LocationType::UNDERWATER_CAVES,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/automation/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'auto_attack_length' => 1,
                'move_down_the_list_every' => 10,
                'selected_monster_id' => $this->monster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('This place is far too special for you to be able to explore. Manual fighting is only allowed here child.', $jsonData['message']);
    }
}
