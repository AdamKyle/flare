<?php

namespace Tests\Unit\Game\Messages\Listeners;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Events\ServerMessageEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\Character\CharacterFactory;

class ServerMessageListenerTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testServerMessageEventLevelUp()
    {
        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        event(new ServerMessageEvent($user, 'level_up'));

        Event::fake();

        event(new ServerMessageEvent($user, 'level_up'));

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testServerMessageEventGoldRush()
    {
        $user = $this->createUser();

        event(new ServerMessageEvent($user, 'gold_rush', '980'));

        Event::fake();

        event(new ServerMessageEvent($user, 'gold_rush', '980'));

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testServerMessageEventGainedItem()
    {
        $user = $this->createUser();

        event(new ServerMessageEvent($user, 'gained_item', 'item name'));

        Event::fake();

        event(new ServerMessageEvent($user, 'gained_item', 'item name'));

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testServerMessageNewDamageSat()
    {
        $user = $this->createUser();

        event(new ServerMessageEvent($user, 'new-damage-stat', 'str'));

        Event::fake();

        event(new ServerMessageEvent($user, 'new-damage-stat', 'str'));

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testServerMessageEventUnKnownType()
    {
        $user = $this->createUser();

        event(new ServerMessageEvent($user, 'xxxx', 'item name'));

        Event::fake();

        event(new ServerMessageEvent($user, 'xxxx', 'item name'));

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testServerMessageAdventure()
    {
        $user = $this->createUser();

        event(new ServerMessageEvent($user, 'adventure', 'message'));

        Event::fake();

        event(new ServerMessageEvent($user, 'adventure', 'message'));

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testServerMessageDeletedItem()
    {
        $user = $this->createUser();

        event(new ServerMessageEvent($user, 'deleted_item', 'message'));

        Event::fake();

        event(new ServerMessageEvent($user, 'deleted_item', 'message'));

        Event::assertDispatched(ServerMessageEvent::class);
    }
}
