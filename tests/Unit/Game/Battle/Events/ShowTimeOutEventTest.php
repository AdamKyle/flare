<?php

namespace Tests\Unit\Game\Battle\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Core\Events\ShowTimeOutEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class ShowTimeOutEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testShowTimeOutEvent()
    {
        $user = $this->createUser();

        event(new ShowTimeOutEvent($user, true, false));

        Event::fake();

        event(new ShowTimeOutEvent($user, true, false));

        Event::assertDispatched(ShowTimeOutEvent::class);
    }
}
