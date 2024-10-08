<?php

namespace Tests\Unit\Game\Messages\Services;

use App\Flare\Models\User;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Messages\Events\MessageSentEvent;
use App\Game\Messages\Models\Message;
use App\Game\Messages\Services\PublicMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class PublicMessageTest extends TestCase
{
    use CreateItem, CreateMessage, CreateNpc, CreateRole, CreateUser, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?PublicMessage $publicMessage;

    private ?User $admin;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->publicMessage = new PublicMessage;
        $this->admin = $this->createAdmin($this->createAdminRole());
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->publicMessage = null;
        $this->admin = null;
    }

    public function testSendPublicMessage()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(MessageSentEvent::class);

        $this->assertGreaterThan(0, Message::count());
    }

    public function testSendPublicMessageForSurfaceColor()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Surface',
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(function (MessageSentEvent $event) {
            return $event->message->map_name === 'SUR';
        });

        $this->assertGreaterThan(0, Message::count());
    }

    public function testSendPublicMessageForLabyrinthColor()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Labyrinth',
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(function (MessageSentEvent $event) {
            return $event->message->map_name === 'LABY';
        });

        $this->assertGreaterThan(0, Message::count());
    }

    public function testSendPublicMessageForDungeonColor()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Dungeons',
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(function (MessageSentEvent $event) {
            return $event->message->map_name === 'DUN';
        });

        $this->assertGreaterThan(0, Message::count());
    }

    public function testSendPublicMessageForHellColor()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Hell',
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(function (MessageSentEvent $event) {
            return $event->message->map_name === 'HELL';
        });

        $this->assertGreaterThan(0, Message::count());
    }

    public function testSendPublicMessageForShadowPlaneColor()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Shadow Plane',
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(function (MessageSentEvent $event) {
            return $event->message->map_name === 'SHP';
        });

        $this->assertGreaterThan(0, Message::count());
    }

    public function testSendPublicMessageForPurgatoryColor()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Purgatory',
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(function (MessageSentEvent $event) {
            return $event->message->map_name === 'PURG';
        });

        $this->assertGreaterThan(0, Message::count());
    }

    public function testSendPublicMessageForTwistedMemoriesColor()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Twisted Memories',
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(function (MessageSentEvent $event) {
            return $event->message->map_name === 'TWM';
        });

        $this->assertGreaterThan(0, Message::count());
    }

    public function testSendPublicMessageForDelusionalMemoriesColor()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Delusional Memories',
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(function (MessageSentEvent $event) {
            return $event->message->map_name === 'DM';
        });

        $this->assertGreaterThan(0, Message::count());
    }

    public function testSendPublicMessageForIcePlaneColor()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'The Ice Plane',
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(function (MessageSentEvent $event) {
            return $event->message->map_name === 'ICE';
        });

        $this->assertGreaterThan(0, Message::count());
    }

    public function testSendPublicMessageDefaultToSurfaceColor()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Some Map',
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(function (MessageSentEvent $event) {
            return $event->message->map_name === 'SUR';
        });

        $this->assertGreaterThan(0, Message::count());
    }

    public function testSendPublicMessageWhenKilledInPvp()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'killed_in_pvp' => true,
        ]);

        $character = $character->refresh();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(MessageSentEvent::class);

        $this->assertGreaterThan(0, Message::count());
        $this->assertEquals(0, Message::first()->x_position);
        $this->assertEquals(0, Message::first()->y_position);
    }

    public function testSendPublicMessageWithLocationHidden()
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::HIDE_CHAT_LOCATION,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        Auth::login($character->user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(MessageSentEvent::class);

        $message = Message::where('hide_location', true)->first();

        $this->assertGreaterThan(0, Message::count());
        $this->assertNotNull($message);
    }

    public function testSendAdminPublicMessage()
    {
        Event::fake();

        $user = $this->createAdmin($this->createAdminRole());

        Auth::login($user);

        $this->publicMessage->postPublicMessage('Test');

        Event::assertDispatched(MessageSentEvent::class);

        $this->assertGreaterThan(0, Message::count());
    }
}
