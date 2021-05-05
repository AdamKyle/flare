<?php

namespace App\Admin\Jobs;

use Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\User;

class BanEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    public $message;

    /**
     * AssignSkillsJob constructor.
     *
     * @param User $user
     * @param string $message
     */
    public function __construct(User $user, string $message) {
        $this->user    = $user;
        $this->message = $message;
    }

    /**
     * Mail the user.
     *
     * @return void
     */
    public function handle() {
        return Mail::to($this->user->email)->send(new GenericMail($this->user, $this->message, 'You have been banned!', true));
    }
}
