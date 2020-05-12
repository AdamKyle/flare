<?php

namespace Tests\Unit\Flare\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Events\UpdateCharacterSheetBroadcastEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UpdateCharacterSheetBroadcastEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testUpdateCharacterSheetBroadcastEvent()
    {
        $user = $this->createUser();

        event(new UpdateCharacterSheetBroadcastEvent([], $user));

        Event::fake();

        event(new UpdateCharacterSheetBroadcastEvent([], $user));

        Event::assertDispatched(UpdateCharacterSheetBroadcastEvent::class);
    }
}
