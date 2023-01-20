<?php

namespace App\Game\Skills\Jobs;

use App\Game\Skills\Services\AlchemyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class ProcessAlchemy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $character;

    private $itemToCraft;

    /**
     * @param Character $character
     * @param int $itemToCraft
     */
    public function __construct(Character $character, int $itemToCraft)
    {
        $this->character   = $character;
        $this->itemToCraft = $itemToCraft;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AlchemyService $alchemyService) {
        $alchemyService->transmute($this->character, $this->itemToCraft);
    }
}
