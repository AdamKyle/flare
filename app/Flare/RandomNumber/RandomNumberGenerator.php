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
     * @return float|int
     */
    public function generateTureRandomNumber(int $max, float $chance): int {
        $randomNumber = mt_rand(1, $max);
        $bonus = $chance * $max;
        $isBonusSuccessful = mt_rand() / mt_getrandmax() <= $chance;

        if ($isBonusSuccessful) {
            $randomNumber += $bonus;
            $randomNumber = min($randomNumber, $max);
        }

        return $randomNumber;
    }
}
