<?php

namespace Tests\Unit\Game\Messages\Services;

use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Services\PublicEntityCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateMonster;

class PublicEntityCommandTest extends TestCase
{
    use CreateCelestials, CreateItem, CreateMessage, CreateMonster, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?PublicEntityCommand $publicEntityCommand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->publicEntityCommand = resolve(PublicEntityCommand::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->publicEntityCommand = null;
    }

    public function test_bail_when_no_character_set()
    {
        Event::fake();

        $this->publicEntityCommand->usPCCommand();

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function test_no_celestials()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->publicEntityCommand->setCharacter($character->user)->usPCCommand();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'There are no celestials in the world right now, child!';
        });
    }

    public function test_use_pc_command()
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

        $this->publicEntityCommand->setCharacter($character->user)->usPCCommand();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message !== 'There are no celestials in the world right now, child!';
        });
    }

    public function test_bail_when_no_character_set_for_pct_command()
    {
        Event::fake();

        $this->publicEntityCommand->usePCTCommand();

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function test_error_out_when_invalid_quest_item_given()
    {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => 'something-invalid',
        ]))->getCharacter();

        $this->publicEntityCommand->setCharacter($character->user)->usePCTCommand();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'Christ child! Something went wrong. Alert The Creator (probs best to head to discord and post in #bugs section. Hover over profile icon, click: Discord to join). /pct is not working!';
        });
    }

    public function test_bail_when_you_dont_have_the_quest_item_for_p_ct()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->publicEntityCommand->setCharacter($character->user)->usePCTCommand();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You are missing a quest item to use /PCT. You need to complete the Quest: Hunting Expedition on Surface.';
        });
    }

    public function test_bail_when_no_celestials()
    {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::TELEPORT_TO_CELESTIAL,
        ]))->getCharacter();

        $this->publicEntityCommand->setCharacter($character->user)->usePCTCommand();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'There are no celestials in the world right now, child!';
        });
    }

    public function test_use_the_pct_command()
    {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::TELEPORT_TO_CELESTIAL,
        ]))->getCharacter();

        $this->publicEntityCommand->setCharacter($character->user)->usePCTCommand();

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
    }
}
