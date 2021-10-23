<?php

namespace App\Game\Maps\Jobs;

use App\Game\Maps\Services\MovementService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Maps\Events\ShowTimeOutEvent;

class PCTTeleport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    protected $character;

    protected $celestialX;

    protected $celestialY;

    protected $celestialName;

    protected $celestialMapName;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @return void
     */
    public function __construct(Character $character, int $celestialX, int $celestialY, string $celestialName, string $celestialMapName)
    {
        $this->character        = $character;
        $this->celestialX       = $celestialX;
        $this->celestialY       = $celestialY;
        $this->celestialName    = $celestialName;
        $this->celestialMapName = $celestialMapName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MovementService $movementService)
    {
        $movement = $movementService->teleport($this->character, $this->celestialX, $this->celestialY, 0 , 0, true);

        if ($movement['status'] === 422) {
            broadcast(new ServerMessageEvent($this->character->user, $movement['message']));
            return;
        }

        $message = 'Child! ' . $this->celestialName  .' is at (X/Y): '. $this->celestialX .'/'. $this->celestialY. ' on the: '. $this->celestialMapName .'Plane. I have teleported you there free of charge!';
        broadcast(new ServerMessageEvent($this->character->user, $message));
    }
}
