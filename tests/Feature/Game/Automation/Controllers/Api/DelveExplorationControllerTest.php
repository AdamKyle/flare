<?php

namespace Tests\Feature\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;

class DelveExplorationControllerTest extends TestCase
{
    use RefreshDatabase;

    private Character $character;

    private Location $location;

    private Monster $monster;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->location = Location::factory()->create([
            'x' => $this->character->map->character_position_x,
            'y' => $this->character->map->character_position_y,
            'game_map_id' => $this->character->map->game_map_id,
            'type' => LocationType::CAVE_OF_MEMORIES,
            'minutes_between_delve_fights' => 5,
        ]);

        $this->monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $this->character->map->game_map_id,
                'only_for_location_type' => LocationType::CAVE_OF_MEMORIES,
                'is_celestial_entity' => false,
                'is_raid_monster' => false,
                'is_raid_boss' => false,
                'raid_special_attack_type' => null,
            ])
            ->getMonster();
    }

    public function testBeginStartsDelve(): void
    {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
                'pack_size' => 5,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Delve has started child. Let us see how long you last shall we? (Max delve time is 8 hours.)', $jsonData['message']);
    }

    public function testStopStopsDelve(): void
    {
        Event::fake();

        CharacterAutomation::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        DelveExploration::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/stop', [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBeginReturns422WhenAttackTypeIsInvalid(): void
    {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => 'invalid',
                'pack_size' => 5,
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
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
                'pack_size' => 5,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You cannot do that while Delve automation is running. Cancel it first.', $jsonData['message']);
    }

    public function testBeginReturns422WhenCharacterIsNotOnCaveOfMemoriesLocation(): void
    {
        Queue::fake();
        Event::fake();

        $this->location->delete();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
                'pack_size' => 5,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You may only delve in locations that allow such an action child.', $jsonData['message']);
    }
}
