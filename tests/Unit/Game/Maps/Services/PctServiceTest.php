<?php

namespace Tests\Unit\Game\Maps\Services;

use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\PctService;
use App\Game\Maps\Services\TraverseService;
use App\Game\Maps\Values\MapName;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;

class PctServiceTest extends TestCase
{
    use CreateCelestials, CreateCharacterAutomation, CreateItem, CreateMonster, RefreshDatabase;

    private ?CharacterFactory $characterFactory = null;

    private ?PctService $pctService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->pctService = new PctService(
            Mockery::mock(TraverseService::class),
            new MapTileValue,
            new AutomationRestrictionService,
            Mockery::mock(LocationService::class),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterFactory = null;
        $this->pctService = null;
    }

    public function test_use_pct_sends_restriction_message_and_returns_false_when_automation_is_running(): void
    {
        Event::fake();

        $character = $this->characterFactory->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
        ]);

        $result = $this->pctService->usePCT($character);

        $this->assertFalse($result);
        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return $event->message === 'You cannot do that while Exploration automation is running. Cancel it first.';
        });
    }

    public function test_use_pct_does_not_send_restriction_message_when_no_automation_is_running(): void
    {
        Event::fake();

        $result = $this->pctService->usePCT($this->characterFactory->getCharacter());

        $this->assertFalse($result);
        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function test_use_pct_prefers_characters_own_private_celestial_over_public(): void
    {
        Event::fake();

        $character = $this->characterFactory->getCharacter();

        $privateMonster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Owned Private Celestial',
        ]);

        $publicMonster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Public Celestial',
        ]);

        $this->createCelestialFight([
            'monster_id' => $privateMonster->id,
            'character_id' => $character->id,
            'x_position' => 5,
            'y_position' => 5,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PRIVATE,
        ]);

        $this->createCelestialFight([
            'monster_id' => $publicMonster->id,
            'character_id' => null,
            'x_position' => 10,
            'y_position' => 10,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        $result = $this->pctService->usePCT($character, false);

        $this->assertTrue($result);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return str_contains($event->message, 'Owned Private Celestial');
        });
    }

    public function test_use_pct_gives_directions_to_event_map_celestial_when_character_has_access_item(): void
    {
        Event::fake();

        $character = $this->characterFactory->inventoryManagement()->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::PURGATORY->value,
        ]))->getCharacter();

        $eventMap = $this->createGameMap([
            'name' => MapName::DELUSIONAL_MEMORIES->value,
        ]);

        $eventMonster = $this->createMonster([
            'game_map_id' => $eventMap->id,
            'name' => 'Event Map Celestial',
        ]);

        $this->createCelestialFight([
            'monster_id' => $eventMonster->id,
            'character_id' => null,
            'x_position' => 12,
            'y_position' => 16,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        $result = $this->pctService->usePCT($character);

        $this->assertTrue($result);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return $event->message === 'Child! Event Map Celestial is at (X/Y): 12/16 on the: Delusional MemoriesPlane.';
        });
    }

    public function test_use_pct_falls_back_to_non_event_map_celestial_when_character_lacks_access_item(): void
    {
        Event::fake();

        $character = $this->characterFactory->getCharacter();

        $eventMap = $this->createGameMap([
            'name' => MapName::DELUSIONAL_MEMORIES->value,
        ]);

        $eventMonster = $this->createMonster([
            'game_map_id' => $eventMap->id,
            'name' => 'Event Map Celestial',
        ]);

        $surfaceMonster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Surface Celestial',
        ]);

        $this->createCelestialFight([
            'monster_id' => $eventMonster->id,
            'character_id' => null,
            'x_position' => 12,
            'y_position' => 16,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        $this->createCelestialFight([
            'monster_id' => $surfaceMonster->id,
            'character_id' => null,
            'x_position' => 32,
            'y_position' => 48,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        $result = $this->pctService->usePCT($character);

        $this->assertTrue($result);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($character) {
            return $event->message === 'Child! Surface Celestial is at (X/Y): 32/48 on the: '.$character->map->gameMap->name.'Plane.';
        });
        Event::assertNotDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return str_contains($event->message, 'Event Map Celestial');
        });
    }
}
