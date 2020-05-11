<?php

namespace Tests\Unit\Game\Messages\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Messages\Events\ServerMessageEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class ServerMessageEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testServerMessageEvent()
    {
        $user = $this->createUser();

        event(new ServerMessageEvent($user, 'test'));

        Event::fake();

        event(new ServerMessageEvent($user, 'test'));

        Event::assertDispatched(ServerMessageEvent::class);
    }
}
