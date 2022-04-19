<?php

namespace App\Game\Maps\Jobs;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\GameMap;
use App\Game\Maps\Services\MovementService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Maps\Events\ShowTimeOutEvent;

class PCTTraverse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    protected $character;

    protected $celestialGameMap;

    protected $celestialFight;

    protected $x;

    protected $y;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @return void
     */
    public function __construct(Character $character, GameMap $celestialGameMap, CelestialFight $celestialFight, int $x, int $y)
    {
        $this->character        = $character;
        $this->celestialGameMap = $celestialGameMap;
        $this->celestialFight   = $celestialFight;
        $this->x                = $x;
        $this->y                = $y;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MovementService $movementService)
    {
        $traverse = $movementService->updateCharacterPlane($this->celestialGameMap->id, $this->character);

        if ($traverse['status'] === 422) {
            broadcast(new ServerMessageEvent($this->character->user, $traverse['message']));
            return;
        }

        broadcast(new ServerMessageEvent($this->character->user, 'Processing teleport...'));

        PCTTeleport::dispatch($this->character, $this->x, $this->y, $this->celestialFight->monster->name, $this->celestialGameMap->name);
    }
}
