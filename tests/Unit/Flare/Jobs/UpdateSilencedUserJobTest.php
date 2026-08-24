<?php

namespace Tests\Unit\Flare\Jobs;

use App\Admin\Events\UpdateAdminChatEvent;
use App\Flare\Jobs\UpdateSilencedUserJob;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class UpdateSilencedUserJobTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_dispatched_job_unsilences_the_user_and_notifies_admins(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $user = $character->user;
        $user->update(['is_silenced' => true, 'can_speak_again_at' => now(), 'message_throttle_count' => 3]);

        $this->createAdminRole();
        $admin = $this->createUser();
        $admin->assignRole('Admin');

        Event::fake([UpdateCharacterBaseDetailsEvent::class, UpdateAdminChatEvent::class]);
        ServerMessageHandler::shouldReceive('handleMessage')->once();

        UpdateSilencedUserJob::dispatch($user);

        $user->refresh();
        $this->assertFalse($user->is_silenced);
        $this->assertNull($user->can_speak_again_at);
        $this->assertSame(0, $user->message_throttle_count);
        Event::assertDispatched(UpdateCharacterBaseDetailsEvent::class);
        Event::assertDispatched(UpdateAdminChatEvent::class);
    }
}
