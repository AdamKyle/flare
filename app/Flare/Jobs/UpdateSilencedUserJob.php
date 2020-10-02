<?php

namespace App\Flare\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;;
use App\Flare\Models\User;
use App\Flare\Events\ServerMessageEvent;
use Cache;

class UpdateSilencedUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

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
     *
     * @return void
     */
    public function handle()
    {
        $this->user->update([
            'is_silenced'            => false,
            'can_speak_again_at'     => null,
            'message_throttle_count' => 0,
        ]);

        $forMessage = 'You are now able to speak and private message again.';

        event(new ServerMessageEvent($this->user->refresh(), 'silenced', $forMessage));
    }
}
