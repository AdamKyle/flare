<?php

namespace Tests\Unit\Admin\Events;

use App\Admin\Events\RefreshUserScreenEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class RefreshUserScreenEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testRefreshUserScreenEvent()
    {
        $user = $this->createUser();

        event(new RefreshUserScreenEvent($user));

        Event::fake();

        event(new RefreshUserScreenEvent($user));

        Event::assertDispatched(RefreshUserScreenEvent::class);
    }
}
