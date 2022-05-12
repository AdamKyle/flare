<?php

namespace App\Game\Maps\Jobs;

use App\Game\Battle\Events\UpdateCharacterStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Maps\Events\ShowTimeOutEvent;

class MoveTimeOutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    protected $characterId;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @return void
     */
    public function __construct(int $characterId)
    {
        $this->characterId = $characterId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $character = Character::find($this->characterId);

        $character->update([
            'can_move' => true,
            'can_move_again_at' => null,
        ]);

        event(new ShowTimeOutEvent($character->refresh()->user, false, true));

        event(new UpdateCharacterStatus($character));
    }
}
