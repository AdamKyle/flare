<?php

namespace App\Flare\ExponentialCurve\Curve;

class ExponentialLevelCurve
{
    /**
     * Generate the skill levels required for the affix.
     *
     * @param  int  $sizeLimit  (25)
     */
    public function generateSkillLevels(int $min = 1, int $max = 400, int $sizeLimit = 25): array
    {
        $skillLevels = [
            'required' => [],
            'trivial' => [],
        ];

        $distance = ceil(($max - $min) / ($sizeLimit - 1)); // Calculate the distance between numbers
        $previousTrivial = 0;
        $counter = 0; // Counter for tracking the size of the arrays

        for ($i = $min; $i <= $max; $i += $distance) {
            $trivial = $i + $distance;

            if ($trivial > $max) {
                $trivial = $max;
            }

            if ($i == $min) {
                $required = $min;
            } else {
                $required = $previousTrivial;
            }

            $skillLevels['required'][] = $required;
            $skillLevels['trivial'][] = $trivial;
            $counter += 1;

            if ($counter >= $sizeLimit) {
                break; // Break the loop if the size limit is reached
            }

            $previousTrivial = $trivial;
        }

        return $skillLevels;
    }
}
