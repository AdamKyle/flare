<?php

namespace App\Game\Battle\Jobs;

use App\Flare\Models\Character;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\ShowTimeOutEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AttackTimeOutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Character $character;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $this->character->update([
            'can_attack' => true,
            'can_attack_again_at' => null,
        ]);

        event(new UpdateCharacterStatus($this->character->refresh()));

        broadcast(new ShowTimeOutEvent($this->character->user, 0));
    }
}
