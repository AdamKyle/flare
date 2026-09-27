<?php

namespace Tests\Feature\Game\Messages\Controllers\Api;

use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;

class CommandsControllerTest extends TestCase
{
    use CreateCelestials, CreateItem, CreateMonster, RefreshDatabase;

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

    public function test_use_pc_command_sends_public_celestial_location(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->createCelestialFight([
            'monster_id' => $this->createMonster([
                'game_map_id' => $character->map->game_map_id,
                'name' => 'Located Celestial',
            ])->id,
            'character_id' => null,
            'conjured_at' => now(),
            'x_position' => 7,
            'y_position' => 9,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => 1000,
            'max_health' => 1000,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/public-entity/', [
                '_token' => csrf_token(),
                'attempt_to_teleport' => false,
            ]);

        $response->assertOk();

        Event::assertDispatched(function (ServerMessageEvent $event) use ($character) {
            return $event->message === 'Child! Located Celestial is at (X/Y): 7/9 on the: '.$character->map->gameMap->name.'Plane.';
        });
    }

    public function test_use_pc_command_sends_no_celestials_message_when_none_exist(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/public-entity/', [
                '_token' => csrf_token(),
                'attempt_to_teleport' => false,
            ]);

        $response->assertOk();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'There are no celestials in the world right now, child!';
        });
    }

    public function test_use_pct_command_requires_the_celestial_teleport_quest_item(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/public-entity/', [
                '_token' => csrf_token(),
                'attempt_to_teleport' => true,
            ]);

        $response->assertOk();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You are missing a quest item to use /PCT. You need to complete the Quest: Hunting Expedition on Surface.';
        });
    }

    public function test_use_pct_command_teleports_character_to_celestial_on_current_plane(): void
    {
        Event::fake();

        $mapTileValue = Mockery::mock(MapTileValue::class);
        $mapTileValue->shouldReceive('setUp')->andReturnSelf();
        $mapTileValue->shouldReceive('canWalk')->with(7, 9)->andReturnTrue();

        $this->app->instance(MapTileValue::class, $mapTileValue);

        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::TELEPORT_TO_CELESTIAL->value,
        ]))->getCharacter();

        $this->createCelestialFight([
            'monster_id' => $this->createMonster([
                'game_map_id' => $character->map->game_map_id,
            ])->id,
            'character_id' => null,
            'conjured_at' => now(),
            'x_position' => 7,
            'y_position' => 9,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => 1000,
            'max_health' => 1000,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/public-entity/', [
                '_token' => csrf_token(),
                'attempt_to_teleport' => true,
            ]);

        $response->assertOk();

        $characterMap = $character->map->refresh();

        $this->assertSame(7, $characterMap->character_position_x);
        $this->assertSame(9, $characterMap->character_position_y);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'Child! I have done it. I have used the magics to move you to: (X/Y) 7/9';
        });
    }

    public function test_use_pct_command_when_dead(): void
    {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::TELEPORT_TO_CELESTIAL->value,
        ]))->getCharacter();

        $character->update([
            'is_dead' => true,
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/public-entity/', [
                '_token' => csrf_token(),
                'attempt_to_teleport' => true,
            ]);

        $response->assertOk();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You are dead. How are you suppose to teleport? Resurrect child!';
        });
    }
}
