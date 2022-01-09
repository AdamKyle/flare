<?php

namespace App\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Core\Events\ResetQuestStorageBroadcastEvent;

class ResetCharacterQuestStorage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Job handler.
     *
     * @return void
     */
    public function handle() {
       Character::chunkById(100, function($characters) {
           foreach ($characters as $character) {
               broadcast(new ResetQuestStorageBroadcastEvent($character->user));
           }
       });
    }
}
