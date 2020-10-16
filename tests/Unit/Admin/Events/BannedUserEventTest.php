<?php

namespace Tests\Unit\Admin\Events;

use App\Admin\Events\BannedUserEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Events\UpdateCharacterAttackBroadcastEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class BannedUserEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testUpdateCharacterAttackBroadcastEvent()
    {
        $user = $this->createUser();

        event(new BannedUserEvent($user));

        Event::fake();

        event(new BannedUserEvent($user));

        Event::assertDispatched(BannedUserEvent::class);
    }
}
