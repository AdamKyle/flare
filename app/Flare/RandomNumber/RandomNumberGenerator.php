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
     * @param int $min
     * @param int $max
     * @return float|int
     * @throws \Exception
     */
    public function generateTureRandomNumber(int $min = 0, int $max = 1): float {
        $randomNumber = random_int($min * 1000, $max * 1000);

        return $randomNumber / 1000;
    }
}
