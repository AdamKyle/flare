<?php

namespace Tests\Unit\Game\Messages\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Messages\Events\PrivateMessageEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;

class PrivateMessageEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testPrivateMessageEvent()
    {
        $user = $this->createUser();
        $from = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter(['can_move' => false], $from)->getCharacter();

        event(new PrivateMessageEvent($from, $user, 'test'));

        Event::fake();

        event(new PrivateMessageEvent($from, $user, 'test'));

        Event::assertDispatched(PrivateMessageEvent::class);
    }
}
