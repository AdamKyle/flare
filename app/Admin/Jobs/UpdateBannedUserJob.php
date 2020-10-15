<?php

namespace App\Admin\Jobs;

use App\Admin\Mail\GenericMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;;
use App\Flare\Models\User;
use App\Flare\Events\ServerMessageEvent;
use Cache;
use Mail;

class UpdateBannedUserJob implements ShouldQueue
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
            'is_banned'   => false,
            'unbanned_at' => null,
        ]);

        Mail::to($this->user->email)->send(new GenericMail($this->user, 'You are now unbanned and may log in again.', 'You have been unbanned'));
    }
}
