<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Service\AttackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MoveUnits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $movementId;

    public $defenderId;

    public $type;

    public function __construct(int $movementId, int $defenderId, string $type)
    {
        $this->movementId = $movementId;
        $this->type       = $type;
        $this->defenderId = $defenderId;
    }

    public function handle(AttackService $attackService) {
        $unitMovement = UnitMovementQueue::find($this->movementId);

        if (is_null($unitMovement)) {
            return;
        }

        switch ($this->type) {
            case 'attack':
                return $attackService->attack($unitMovement, $this->defenderId);
            default:
                return;
        }
    }
}