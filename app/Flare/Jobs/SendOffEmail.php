<?php

namespace App\Flare\Jobs;

use Illuminate\Support\Facades\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;
use Asahasrabuddhe\LaravelMJML\Mail\Mailable;

class SendOffEmail implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User $user
     */
    public User $user;

    /**
     * @var Mailable $mailable
     */
    public Mailable $mailable;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param Mailable $mailable
     */
    public function __construct(User $user, Mailable $mailable) {
        $this->user     = $user;
        $this->mailable = $mailable;
    }

    /**
     * Processes the type of simulation test we want.
     *
     * @return void
     */
    public function handle(): void {
        Mail::to($this->user)->send($this->mailable);
    }
}
