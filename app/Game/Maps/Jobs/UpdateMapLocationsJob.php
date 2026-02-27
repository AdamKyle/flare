<?php

namespace App\Game\Maps\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Raid;
use App\Game\Maps\Events\UpdateMapLocations;
use App\Game\Maps\Services\LocationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateMapLocationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $characterId;

    protected ?int $raidId = null;

    /**
     * Create a new job instance.
     */
    public function __construct(int $characterId, ?int $raidId = null)
    {
        $this->characterId = $characterId;
        $this->raidId = $raidId;
    }

    /**
     * Execute the job.
     */
    public function handle(LocationService $locationService): void
    {
        $character = Character::find($this->characterId);

        if (is_null($character)) {
            return;
        }

        $raid = Raid::find($this->raidId);

        $locationData = $locationService->fetchLocationData($character);
        $corruptedLocationData = [];

        if (! is_null($raid)) {
            $corruptedLocationData = $locationService->fetchCorruptedLocationData($raid);
        }

        $mergedData = collect(array_merge($locationData, $corruptedLocationData));

        event(new UpdateMapLocations($character->user, $mergedData));
    }
}
