<?php

namespace App\Flare\Jobs;

use App\Flare\Mail\GenericMail;
use App\Flare\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOffEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;

    public GenericMail $mailable;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, GenericMail $mailable)
    {
        $this->user = $user;
        $this->mailable = $mailable;
    }

    /**
     * Processes the type of simulation test we want.
     */
    public function handle(): void
    {
        Mail::to($this->user)->send($this->mailable);
    }
}
