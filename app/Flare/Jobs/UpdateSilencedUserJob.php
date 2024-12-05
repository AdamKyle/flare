<?php

namespace App\Flare\Jobs;

use App\Admin\Events\UpdateAdminChatEvent;
use App\Flare\Models\User;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateSilencedUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->update([
            'is_silenced' => false,
            'can_speak_again_at' => null,
            'message_throttle_count' => 0,
        ]);

        $forMessage = 'You are now able to speak and private message again.';

        $user = $this->user->refresh(0);

        ServerMessageHandler::handleMessage($user, CharacterMessageTypes::SILENCED, $forMessage);

        event(new UpdateTopBarEvent($user->character));

        $adminUser = User::with('roles')->whereHas('roles', function ($q) {
            $q->where('name', 'Admin');
        })->first();

        broadcast(new UpdateAdminChatEvent($adminUser));
    }
}
