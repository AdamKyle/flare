<?php

namespace Tests\Unit\Core\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Core\Events\UpdateShopInventoryBroadcastEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UpdateShopInventoryBroadcastEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testUpdateShopInventory()
    {
        $user = $this->createUser();

        event(new UpdateShopInventoryBroadcastEvent([], $user));

        Event::fake();

        event(new UpdateShopInventoryBroadcastEvent([], $user));

        Event::assertDispatched(UpdateShopInventoryBroadcastEvent::class);
    }
}
