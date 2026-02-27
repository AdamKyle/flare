<?php

namespace App\Game\Maps\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Raid;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Maps\Events\ShowTimeOutEvent;
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

    /**
     * @var int $characterId
     */
    protected int $characterId;

    /**
     * @var int|null $raidId
     */
    protected ?int $raidId = null;

    /**
     * Create a new job instance.
     *
     * @param int $characterId
     * @param int|null $raidId
     */
    public function __construct(int $characterId, ?int $raidId = null)
    {
        $this->characterId = $characterId;
        $this->raidId = $raidId;
    }

    /**
     * Execute the job.
     *
     * @param LocationService $locationService
     * @return void
     */
    public function handle(LocationService $locationService): void
    {
        $character = Character::find($this->characterId);

        if (is_null($character)) {
            return;
        }

        $raid = Raid::find($this->raidId);

        $locationData =  $locationService->fetchLocationData($character->map->game_map_id)->merge($locationService->fetchCorruptedLocationData($raid));

        event(new UpdateMapLocations($character->user, $locationData));
    }
}
