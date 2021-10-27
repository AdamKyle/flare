<?php

namespace Tests\Unit\Game\Messages\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Messages\Events\MessageSentEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class MessageSentEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testMessageSentEvent()
    {
        $user = $this->createUser();

        $user->messages()->create(['message' => 'hello']);

        event(new MessageSentEvent($user, $user->messages()->first()));

        Event::fake();

        event(new MessageSentEvent($user, $user->messages()->first()));

        Event::assertDispatched(MessageSentEvent::class);
    }
}
