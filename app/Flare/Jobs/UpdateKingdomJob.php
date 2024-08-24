<?php

namespace App\Flare\Jobs;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Service\KingdomUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateKingdomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $kingdomId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $kingdomId)
    {
        $this->kingdomId = $kingdomId;
    }

    public function handle(KingdomUpdateService $kingdomUpdateService)
    {

        $kingdom = Kingdom::find($this->kingdomId);

        if (is_null($kingdom)) {
            return;
        }

        $kingdomUpdateService->setKingdom($kingdom)->updateKingdom();
    }
}
