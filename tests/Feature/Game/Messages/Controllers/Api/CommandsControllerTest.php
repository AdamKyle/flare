<?php

namespace Tests\Feature\Game\Messages\Controllers\Api;

use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;

class CommandsControllerTest extends TestCase
{
    use RefreshDatabase, CreateMonster, CreateCelestials, CreateItem;


    private ?CharacterFactory $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testUsePcCommand()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->createCelestialFight([
            'monster_id' => $this->createMonster()->id,
            'character_id' => $character->id,
            'conjured_at' => now(),
            'x_position' => 0,
            'y_position' => 0,
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

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message !== 'There are no celestials in the world right now, child!';
        });

        $this->assertEquals(200, $response->status());
    }

    public function testUsePctCommand()
    {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::TELEPORT_TO_CELESTIAL,
        ]))->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/public-entity/', [
                '_token' => csrf_token(),
                'attempt_to_teleport' => true,
            ]);

        $this->createCelestialFight([
            'monster_id' => $this->createMonster()->id,
            'character_id' => $character->id,
            'conjured_at' => now(),
            'x_position' => 0,
            'y_position' => 0,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => 1000,
            'max_health' => 1000,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals(200, $response->status());
    }

    public function testUsePctCommandWhenDead()
    {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::TELEPORT_TO_CELESTIAL,
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

        $this->createCelestialFight([
            'monster_id' => $this->createMonster()->id,
            'character_id' => $character->id,
            'conjured_at' => now(),
            'x_position' => 0,
            'y_position' => 0,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => 1000,
            'max_health' => 1000,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You are dead. How are you suppose to teleport? Resurrect child!';
        });

        $this->assertEquals(200, $response->status());
    }
}
