<?php

namespace App\Game\Kingdoms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;

class GiveKingdomsToNPC implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function handle(GiveKingdomsToNpcHandler $giveKingdomsToNpcHandler) {
        $giveKingdomsToNpcHandler->giveKingdoms($this->user);
    }
}
