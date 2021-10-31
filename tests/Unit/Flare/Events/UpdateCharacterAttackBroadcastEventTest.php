<?php

namespace Tests\Unit\Flare\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Events\UpdateCharacterAttackBroadcastEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UpdateCharacterAttackBroadcastEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testUpdateCharacterAttackBroadcastEvent()
    {
        $user = $this->createUser();

        event(new UpdateCharacterAttackBroadcastEvent([], $user));

        Event::fake();

        event(new UpdateCharacterAttackBroadcastEvent([], $user));

        Event::assertDispatched(UpdateCharacterAttackBroadcastEvent::class);
    }
}
