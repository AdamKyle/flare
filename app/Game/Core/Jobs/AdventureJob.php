<?php

namespace App\Game\Core\Jobs;

use App\Flare\Models\Adventure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Core\Services\AdventureService;

class AdventureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $character;

    protected $adventure;

    protected $levelsAtATime;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Character $character, Adventure $adventure, $levelsAtATime = 'all')
    {
        $this->character     = $character;
        $this->adventure     = $adventure;
        $this->levelsAtATime = $levelsAtATime;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->character->adventureLogs->where('adventure_id', $this->adventure->id)->isNotEmpty()) {
            $adevntureService = resolve(AdventureService::class, [
                'character'        => $this->character,
                'adventure'        => $this->adventure,
                'levels_at_a_time' => $this->levelsAtATime,
            ]);

            $adevntureService->processAdventure();
        }
    }
}
