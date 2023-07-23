<?php

namespace App\Flare\RandomNumber;

class LotteryRandomNumberGenerator {

    /**
     * Simulate rolling above 95 on a 100-sided die with a variable probability based on the number of loot boxes purchased
     *
     * @param int $nLootBoxes Number of loot boxes purchased
     * @return int
     */
    public function generateNumber(int $nLootBoxes): int {
        $maxLootBoxes = 100;

        $probabilityGettingItem = $this->calculateProbability($nLootBoxes, $maxLootBoxes);

        $roll = rand(1, 100);

        if ($roll <= $probabilityGettingItem * 100) {
            return rand(96, 100);
        } else {
            return rand(1, 95);
        }
    }

    /**
     * Calculate the probability mass function of a binomial distribution
     *
     * @param int $n Number of trials
     * @param int $k Number of successful trials
     * @param float $p Probability of success for each trial
     * @param float $p_zero Probability of failure for each trial (0 items obtained)
     * @return float
     */
    protected function binomialDistribution(int $n, int $k, float $p, float $p_zero): float {
        $coefficient = $this->binomialCoefficient($n, $k);
        $probability = pow($p, $k) * pow(1 - $p, $n - $k);

        return $coefficient * $probability * pow($p_zero, $n - $k);
    }

    /**
     * Calculate the binomial coefficient (n choose k)
     *
     * @param int $n
     * @param int $k
     * @return float
     */
    protected function binomialCoefficient(int $n, int $k): float {
        return $this->factorial($n) / ($this->factorial($k) * $this->factorial($n - $k));
    }

    /**
     * Calculate the factorial of a given number
     *
     * @param int $n
     * @return int
     */
    private function factorial(int $n): int {
        if ($n <= 1) {
            return 1;
        } else {
            return $n * $this->factorial($n - 1);
        }
    }

    /**
     * Calculate the probability of getting at least one item from a given number of loot boxes
     *
     * @param int $nLootBoxes Number of loot boxes purchased
     * @param int $maxLootBoxes Maximum number of loot boxes to buy
     * @return float
     */
    private function calculateProbability(int $nLootBoxes, int $maxLootBoxes): float {
        $p = ($maxLootBoxes - $nLootBoxes + 1) / $maxLootBoxes;
        return 1 - pow(1 - $p, $nLootBoxes);
    }
}





