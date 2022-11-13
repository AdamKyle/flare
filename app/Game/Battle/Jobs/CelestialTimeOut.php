<?php

namespace App\Game\Battle\Jobs;

use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\UpdateCharacterCelestialTimeOut;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Core\Events\ShowTimeOutEvent;

class CelestialTimeOut implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    protected Character $character;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @return void
     */
    public function __construct(Character $character) {
        $this->character = $character;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void {

        $this->character->update([
            'can_engage_celestials'          => true,
            'can_engage_celestials_again_at' => null,
        ]);

        event(new UpdateCharacterStatus($this->character->refresh()));

        broadcast(new UpdateCharacterCelestialTimeOut($this->character->user, 0));
    }
}
