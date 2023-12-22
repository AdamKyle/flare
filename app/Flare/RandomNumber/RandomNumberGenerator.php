<?php

namespace App\Flare\RandomNumber;

class RandomNumberGenerator {

    /**
     * Generate a random number using a randomly generated seed.
     *
     * @param integer $min
     * @param integer $max
     * @return integer
     */
    public function generateRandomNumber(int $min = 1, int $max = 1000): int {
        $seed = mt_rand();

        mt_srand($seed);

        $randomNumbers = array();

        for ($i = 0; $i < 10; $i++) {
            $randomNumbers[] = mt_rand($min, $max);
        }

        $selectedNumberIndex = array_rand($randomNumbers);

        return $randomNumbers[$selectedNumberIndex];
    }

    /**
     * Generates a true random number.
     * @param int $max
     * @param float $chance
     * @return int
     */
    public function generateTrueRandomNumber(int $max, float $chance = 0): int {

        if ($chance >= 1.0) {
            return $max;
        }

        $randomNumber = mt_rand(1, $max);

        if ($chance <= 0) {
            return $randomNumber;
        }

        $bonus = (int) round($chance * $max);

        $fraction = 0.5;

        $isBonusSuccessful = mt_rand() / mt_getrandmax() <= $chance * 0.5;

        if ($isBonusSuccessful) {

            if ($max > 100) {
                $randomNumber += (int)round($fraction * $bonus);
            } else {
                $randomNumber += $bonus;
            }

            $randomNumber = min($randomNumber, $max);
        }

        return $randomNumber;
    }

}
