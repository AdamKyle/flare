<?php

namespace App\Game\Kingdoms\Jobs;

use App\Game\Kingdoms\Events\UpdateUnitMovementLogs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Service\AttackService;
use App\Game\Kingdoms\Service\UnitReturnService;

class MoveUnits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $character;

    public $movementId;

    public $defenderId;

    public $type;

    public function __construct(int $movementId, int $defenderId, string $type, Character $character)
    {
        $this->movementId = $movementId;
        $this->type       = $type;
        $this->defenderId = $defenderId;
        $this->character  = $character;
    }

    public function handle(AttackService $attackService, UnitReturnService $unitReturnService) {
        $unitMovement = UnitMovementQueue::find($this->movementId);

        if (is_null($unitMovement)) {
            return;
        }

        switch ($this->type) {
            case 'attack':
                $unitMovement->update([
                    'is_moving' => false,
                ]);

                $unitMovement = $unitMovement->refresh();

                UpdateUnitMovementLogs::dispatch($unitMovement->character);

                return $attackService->attack($unitMovement, $this->character, $this->defenderId);
            case 'return':
                return $unitReturnService->returnUnits($unitMovement, $this->character);
            case 'recalled':
                return $unitReturnService->recallUnits($unitMovement, $this->character);
            default:
                return;
        }
    }
}
