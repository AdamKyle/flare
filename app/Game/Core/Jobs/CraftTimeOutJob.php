<?php

namespace App\Game\Core\Jobs;

use App\Flare\Models\Character;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\ShowCraftingTimeOutEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CraftTimeOutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character
     */
    protected $character;

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
     *
     * @return void
     */
    public function handle()
    {

        $this->character->update([
            'can_craft' => true,
            'can_craft_again_at' => null,
        ]);

        broadcast(new UpdateCharacterStatus($this->character->refresh()));

        broadcast(new ShowCraftingTimeOutEvent($this->character->user, 0));
    }
}
