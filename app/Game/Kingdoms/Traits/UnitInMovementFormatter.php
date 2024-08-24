<?php

namespace App\Game\Kingdoms\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

trait UnitInMovementFormatter
{
    /**
     * Formats the units in movement.
     */
    public function format(Collection $unitsInMovement): SupportCollection
    {
        return $unitsInMovement->transform(function ($unitInMovement) {
            $unitInMovement->from_kingdom_name = $unitInMovement->from_kingdom->name.' (X/Y) '.$unitInMovement->from_kingdom->x_position.'/'.$unitInMovement->from_kingdom->y_position;
            $unitInMovement->to_kingdom_name = $unitInMovement->to_kingdom->name.' (X/Y) '.$unitInMovement->to_kingdom->x_position.'/'.$unitInMovement->to_kingdom->y_position;

            $totalAmount = 0;

            foreach ($unitInMovement->units_moving as $key => $unitDetails) {
                if ($key === 'new_units') {
                    $totalAmount = $this->fetchTotalAmount($unitDetails);
                } elseif ($key !== 'old_units') {
                    $totalAmount += $unitDetails['amount'];
                }
            }

            $unitInMovement->total_amount = $totalAmount;

            return $unitInMovement;
        });
    }

    /**
     * Gets total amount of units.
     */
    protected function fetchTotalAmount(array $unitDetails): int
    {
        $totalAmount = 0;

        foreach ($unitDetails as $index => $details) {
            $totalAmount += $details['amount'];
        }

        return $totalAmount;
    }
}
