<?php

namespace Tests\Feature\Game\Automation\Controllers\Api;

use App\Flare\Models\CharacterAutomation;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateDelveExploration;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class DelveExplorationControllerTest extends TestCase
{
    use CreateCharacterAutomation, CreateDelveExploration, CreateItem, CreateLocation, CreateMonster, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_begin_returns_422_when_attack_type_is_invalid(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/delve/'.$character->id.'/start', [
                '_token' => csrf_token(),
                'attack_type' => 'not-a-real-attack-type',
            ]);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_begin_returns_422_when_another_automation_is_running(): void
    {
        $character = $this->character->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/delve/'.$character->id.'/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackType::ATTACK->value,
            ]);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_begin_returns_422_when_character_is_not_in_a_delve_location(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/delve/'.$character->id.'/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackType::ATTACK->value,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertSame('You may only delve in locations that allow such an action child.', $jsonData['message']);
    }

    public function test_begin_starts_the_delve_when_character_is_in_a_delve_location(): void
    {
        Queue::fake();
        Event::fake();

        $character = $this->character->getCharacter();

        $this->createLocation([
            'type' => LocationType::CAVE_OF_MEMORIES->value,
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'minutes_between_delve_fights' => 3,
        ]);

        $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/delve/'.$character->id.'/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackType::ATTACK->value,
                'pack_size' => 1,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Delve has started', $jsonData['message']);
    }

    public function test_status_returns_the_characters_delve_status(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/delve/'.$character->id.'/status');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($jsonData['active']);
    }

    public function test_quest_item_detail_returns_item_data_for_quest_item(): void
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem(['type' => 'quest']);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/delve/'.$character->id.'/quest-item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($item->id, $jsonData['item']['id']);
    }

    public function test_quest_item_detail_rejects_non_quest_item(): void
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem(['type' => 'weapon']);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/delve/'.$character->id.'/quest-item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertSame('Item is not a quest item.', $jsonData['message']);
    }

    public function test_dismiss_dismisses_completed_delve_and_returns_status(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $delve = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'panel_dismissed_at' => null,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/delve/'.$character->id.'/dismiss', [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($delve->fresh()->panel_dismissed_at);
    }

    public function test_stop_stops_the_running_delve(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/delve/'.$character->id.'/stop', [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(0, CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE->value)->count());
    }
}
