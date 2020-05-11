<?php

namespace Tests\Unit\Game\Battle\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Battle\Events\UpdateTopBarBroadcastEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UpdateTopBarBroadcastEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testShowTimeOutEvent()
    {
        $user = $this->createUser();

        event(new UpdateTopBarBroadcastEvent([], $user));

        Event::fake();

        event(new UpdateTopBarBroadcastEvent([], $user));

        Event::assertDispatched(UpdateTopBarBroadcastEvent::class);
    }
}
