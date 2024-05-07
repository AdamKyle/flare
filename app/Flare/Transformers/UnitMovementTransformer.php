<?php

namespace App\Flare\Transformers;


use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use League\Fractal\TransformerAbstract;

class UnitMovementTransformer extends TransformerAbstract {

    /**
     * @param UnitMovementQueue $unitMovementQueue
     * @return array
     */
    public function transform(UnitMovementQueue $unitMovementQueue): array {

        return [
            'id'                => $unitMovementQueue->id,
            'character_id'      => $unitMovementQueue->character_id,
            'from_kingdom_name' => $this->getKingdomName($unitMovementQueue->from_kingdom_id),
            'to_kingdom_name'   => $this->getKingdomName($unitMovementQueue->to_kingdom_id),
            'time_left'         => $this->getTimeLeft($unitMovementQueue),
            'moving_to_x'       => $unitMovementQueue->moving_to_x,
            'moving_to_y'       => $unitMovementQueue->moving_to_y,
            'from_x'            => $unitMovementQueue->from_x,
            'from_y'            => $unitMovementQueue->from_y,
            'reason'            => $this->getReason($unitMovementQueue),
            'is_recalled'       => $unitMovementQueue->is_recalled,
            'is_returning'      => $unitMovementQueue->is_returning,
            'is_moving'         => $unitMovementQueue->is_moving,
            'is_attacking'      => $unitMovementQueue->is_attacking,
            'resources_requested' => $unitMovementQueue->resources_requested,
        ];
    }

    /**
     * Get kingdom name.
     *
     * @param int $kingdomId
     * @return string
     */
    protected function getKingdomName(int $kingdomId): string {
        return Kingdom::find($kingdomId)->name;
    }

    /**
     * Get time left in seconds.
     *
     * @param UnitMovementQueue $unitMovementQueue
     * @return int
     */
    protected function getTimeLeft(UnitMovementQueue $unitMovementQueue): int {
        $secondsLeft = $unitMovementQueue->completed_at->diffInSeconds(now());

        if ($secondsLeft > 0) {
            return $secondsLeft;
        }

        return 0;
    }

    protected function getReason(UnitMovementQueue $unitMovementQueue): string {
        if ($unitMovementQueue->is_attacking) {
            return 'Currently attacking';
        }

        if ($unitMovementQueue->is_recalled) {
            return 'Recalled units';
        }

        if ($unitMovementQueue->is_returning) {
            return 'Returning home';
        }

        if ($unitMovementQueue->is_moving) {
            return 'Called for Reinforcement';
        }

        if ($unitMovementQueue->resources_requested) {
            return 'Resources were requested';
        }

        return 'ERROR: unknown.';
    }
}
