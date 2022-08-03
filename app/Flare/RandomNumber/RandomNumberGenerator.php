<?php

namespace App\Flare\RandomNumber;

class RandomNumberGenerator {

    /**
     * Generate a new random number.
     *
     * This comes from stackoverflow and uses the Marsaglia polar method
     * with Boundaries.
     *
     * An example of how to use this:
     *
     * $value = rand_polar(1, 50, 1, 100);
     *
     * We use a mean and standard variance to determine the final number based on Min and Max.
     *
     * @param float|int $m
     * @param float|int $s
     * @param int $min
     * @param int $max
     * @return float
     * @link https://stackoverflow.com/a/21498411
     * @link https://en.wikipedia.org/wiki/Marsaglia_polar_method
     */
    public function generateRandomNumber(float|int $mean = 0.0, float|int $standardVariance = 1.0, int $min = 0, int $max = 20) {
        do {
            do {
                $x = (float)mt_rand()/(float)mt_getrandmax();
                $y = (float)mt_rand()/(float)mt_getrandmax();

                $q = pow((2 * $x - 1), 2) + pow((2 * $y - 1), 2);
            }
            while ($q > 1);

            $p = sqrt((-2 * log($q))/$q);

            $y = ((2 * $y - 1) * $p);
            $x = ((2 * $x - 1) * $p);
            $rand = $y * $standardVariance + $mean;
        }
        while($rand > $max || $rand < $min);
        return $rand;
    }
}
