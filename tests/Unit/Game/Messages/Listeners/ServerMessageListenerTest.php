<?php

namespace Tests\Unit\Game\Messages\Listeners;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Events\ServerMessageEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;

class ServerMessageListenerTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testServerMessageEventLevelUp()
    {
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter(['can_move' => false], $user)->getCharacter();

        event(new ServerMessageEvent($user, 'level_up'));

        Event::fake();

        event(new ServerMessageEvent($user, 'level_up'));

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testServerMessageEventGoldRush()
    {
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter(['can_move' => false], $user)->getCharacter();

        event(new ServerMessageEvent($user, 'gold_rush', '980'));

        Event::fake();

        event(new ServerMessageEvent($user, 'gold_rush', '980'));

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testServerMessageEventGainedItem()
    {
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter(['can_move' => false], $user)->getCharacter();

        event(new ServerMessageEvent($user, 'gained_item', 'item name'));

        Event::fake();

        event(new ServerMessageEvent($user, 'gained_item', 'item name'));

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testServerMessageEventUnKnownType()
    {
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter(['can_move' => false], $user)->getCharacter();

        event(new ServerMessageEvent($user, 'xxxx', 'item name'));

        Event::fake();

        event(new ServerMessageEvent($user, 'xxxx', 'item name'));

        Event::assertDispatched(ServerMessageEvent::class);
    }
}
