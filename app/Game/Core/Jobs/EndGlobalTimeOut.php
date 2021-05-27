<?php

namespace App\Game\Core\Jobs;

use App\Flare\Models\User;
use App\Game\Core\Events\GlobalTimeOut;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EndGlobalTimeOut implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User $user
     */
    private $user;

    /**
     * EndGlobalTimeOut constructor.
     *
     * @param User $user
     */
    public function __construct(User $user) {
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
            'timeout_until' => null,
        ]);

        broadcast(new GlobalTimeOut($this->user->refresh()));
    }
}
