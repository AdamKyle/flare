<?php

namespace App\Flare\AffixGenerator\Curve;

class ExponentialLevelCurve {

    /**
     * Generate the skill levels required for the affix.
     *
     * @param int $min
     * @param int $max
     * @return array
     */
    public function generateSkillLevels(int $min, int $max): array {
        $skillLevels = [
            'required' => [],
            'trivial'  => [],
        ];

        $distance = 15; // Initial distance
        $previousTrivial = 0;
    
        for ($i = $min; $i <= $max; $i += $distance) {
            $trivial = $i + $distance;
    
            if ($trivial > $max) {
                $trivial = $max;
            }
    
            if ($i == $min) {
                $required = 1;
            } else {
                $required = $previousTrivial;
            }
    
            if ($trivial - $required <= 15 && $required > 380) {
                $skillLevels['required'][] = $required;
                $skillLevels['trivial'][] = $max;

                return $skillLevels;
            }

            $skillLevels['required'][] = $required;
            $skillLevels['trivial'][]  = $trivial;
    
            if ($trivial >= $max) {
                $skillLevels['required'][count($skillLevels['required']) - 1] = $required;
                $skillLevels['trivial'][count($skillLevels['trivial']) - 1]   = $max;

                break;
            }
    
            // Calculate next distance
            $remainingDistance = $max - $trivial;
            $distance          = min(max(4, $remainingDistance), rand(5, 9));
    
            $previousTrivial   = $trivial;
        }
    
        return $skillLevels;
    }
}