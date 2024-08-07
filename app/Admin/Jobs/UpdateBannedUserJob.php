<?php

namespace App\Admin\Jobs;

use App\Flare\Mail\GenericMail;
use App\Flare\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class UpdateBannedUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User
     */
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
     * Update the user from being banned to not banned.
     *
     * Email the user telling them they are unbanned.
     *
     * @return void
     */
    public function handle()
    {
        $this->user->update([
            'is_banned' => false,
            'unbanned_at' => null,
        ]);

        return Mail::to($this->user->email)->send(new GenericMail($this->user, 'You are now unbanned and may log in again.', 'You have been unbanned'));
    }
}
