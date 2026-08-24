<?php

namespace Tests\Unit\Flare\Handlers;

use App\Flare\Handlers\MessageThrottledHandler;
use App\Flare\Jobs\UpdateSilencedUserJob;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class MessageThrottledHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_increase_throttle_count_increments_and_refreshes_the_user(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $user = $character->user;

        (new MessageThrottledHandler())->forUser($user)->increaseThrottleCount();

        $this->assertSame(1, $user->refresh()->message_throttle_count);
    }

    public function test_silence_does_nothing_when_throttle_count_is_below_three(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $user = $character->user;
        $user->update(['message_throttle_count' => 2]);

        Queue::fake();

        (new MessageThrottledHandler())->forUser($user)->silence();

        $this->assertFalse($user->refresh()->is_silenced);
        Queue::assertNothingPushed();
    }

    public function test_silence_silences_the_user_when_throttle_count_reaches_three(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $user = $character->user;
        $user->update(['message_throttle_count' => 3]);

        Queue::fake();
        ServerMessageHandler::shouldReceive('handleMessage')->once();

        (new MessageThrottledHandler())->forUser($user)->silence();

        $user->refresh();
        $this->assertTrue($user->is_silenced);
        $this->assertNotNull($user->can_speak_again_at);
        Queue::assertPushed(UpdateSilencedUserJob::class);
    }
}
