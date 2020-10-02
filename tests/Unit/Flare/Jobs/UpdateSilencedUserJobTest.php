<?php

namespace Tests\Unit\Flare\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Handlers\MessageThrottledHandler;
use App\Flare\Jobs\UpdateSilencedUserJob;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UpdateSilencedUserJobTest extends TestCase
{
    use RefreshDatabase, CreateUser;

    public function testUpdateSilencedJob()
    {
        $user = $this->createUser(
            [
                'is_silenced'            => true,
                'can_speak_again_at'     => now()->addMinutes(5),
                'message_throttle_count' => 4,
            ]
        );

        UpdateSilencedUserJob::dispatch($user);

        $user = $user->refresh();

        $this->assertFalse($user->is_silenced);
        $this->assertNull($user->can_speak_again_at);
        $this->assertEquals(0, $user->message_throttle_count);
    }

    
}
