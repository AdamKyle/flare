<?php

namespace Tests\Unit\Flare\Handlers;

use Event;
use Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Handlers\MessageThrottledHandler;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class MessageThrottledHandlerTest extends TestCase
{
    use RefreshDatabase, CreateUser;

    public function testIncreaseMessageThrottleCount()
    {
        $handler = resolve(MessageThrottledHandler::class);
        $user    = $this->createUser();

        $handler->forUser($user)->increaseThrottleCount()->silence();

        $user = $user->refresh();

        $this->assertFalse($user->is_silenced);
        $this->assertNull($user->can_speak_again_at);
        $this->assertEquals(1, $user->message_throttle_count);
    }

    public function testUserIsSilenced()
    {

        Queue::fake();
        Event::fake();

        $handler = resolve(MessageThrottledHandler::class);
        $user    = $this->createUser([
            'message_throttle_count' => 3
        ]);

        $handler->forUser($user)->increaseThrottleCount()->silence();

        $user = $user->refresh();

        $this->assertTrue($user->is_silenced);
        $this->assertNotNull($user->can_speak_again_at);

        // Anything higher than three they cannot speak.
        $this->assertEquals(4, $user->message_throttle_count);
    }
}
