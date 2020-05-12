<?php

namespace Tests\Unit\Flare\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Events\UpdateCharacterInventoryBroadcastEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UpdateCharacterInventoryBroadcastEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testUpdateCharacterInventoryBroadcastEvent()
    {
        $user = $this->createUser();

        event(new UpdateCharacterInventoryBroadcastEvent([], $user));

        Event::fake();

        event(new UpdateCharacterInventoryBroadcastEvent([], $user));

        Event::assertDispatched(UpdateCharacterInventoryBroadcastEvent::class);
    }
}
