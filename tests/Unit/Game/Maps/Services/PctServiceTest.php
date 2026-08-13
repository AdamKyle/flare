<?php

namespace Tests\Unit\Game\Maps\Services;

use App\Flare\Models\Character;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Maps\Services\PctService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateMonster;

class PctServiceTest extends TestCase
{
    use CreateCelestials, CreateCharacterAutomation, CreateMonster, RefreshDatabase;

    private ?Character $character = null;

    private ?PctService $pctService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->pctService = resolve(PctService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->pctService = null;
    }

    public function test_use_pct_sends_restriction_message_and_returns_false_when_automation_is_running(): void
    {
        Event::fake();

        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
        ]);

        $result = $this->pctService->usePCT($this->character);

        $this->assertFalse($result);
        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_use_pct_does_not_send_restriction_message_when_no_automation_is_running(): void
    {
        Event::fake();

        $result = $this->pctService->usePCT($this->character);

        $this->assertFalse($result);
        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function test_use_pct_prefers_characters_own_private_celestial_over_public(): void
    {
        Event::fake();

        $privateMonster = $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
            'name' => 'Owned Private Celestial',
        ]);

        $publicMonster = $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
            'name' => 'Public Celestial',
        ]);

        $this->createCelestialFight([
            'monster_id' => $privateMonster->id,
            'character_id' => $this->character->id,
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

        $result = $this->pctService->usePCT($this->character, false);

        $this->assertTrue($result);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return str_contains($event->message, 'Owned Private Celestial');
        });
    }
}
