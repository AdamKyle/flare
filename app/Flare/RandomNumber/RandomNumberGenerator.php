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
    public function generateTrueRandomNumber(int $max, float $chance): int {

        if ($chance >= 1.0) {
            return $max;
        }

        $randomNumber = mt_rand(1, $max);
        $bonus = $chance * $max * 0.1; // Adjust 0.1 to your desired factor

        $fraction = 0.5; // Adjust to your desired fraction

        $isBonusSuccessful = mt_rand() / mt_getrandmax() <= $chance * 0.5; // Adjust 0.5 to your desired factor

        if ($isBonusSuccessful) {
            $randomNumber += $fraction * $bonus;
            $randomNumber = min($randomNumber, $max);
        }

        return $randomNumber;
    }

}
