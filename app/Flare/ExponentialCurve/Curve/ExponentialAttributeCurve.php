<?php

namespace App\Flare\ExponentialCurve\Curve;

class ExponentialAttributeCurve {

    /**
     * @var integer|float $min
     */
    private int|float $min;

    /**
     * @var integer|float $max
     */
    private int|float $max;

    /**
     * @var integer|float $range
     */
    private int|float $range;

    /**
     * @var integer|float $increase
     */
    private int|float $increase;

    /**
     * @var integer|float $previousY
     */
    private int|float $previousY = 0;

    /**
     * Set minimum value
     *
     * @param integer|float $min
     * @return void
     */
    public function setMin(int|float $min): ExponentialAttributeCurve {
        $this->min = $min;

        return $this;
    }

    /**
     * Set maximum value
     *
     * @param integer|float $max
     * @return void
     */
    public function setMax(int|float $max): ExponentialAttributeCurve {
        $this->max = $max;

        return $this;
    }

    /**
     * Set increase amout
     *
     * @param integer|float $increase
     * @return void
     */
    public function setIncrease(int|float $increase): ExponentialAttributeCurve {
        $this->increase = $increase;

        return $this;
    }

    /**
     * Set range amount
     *
     * @param integer|float $range
     * @return void
     */
    public function setRange(int|float $range): ExponentialAttributeCurve {
        $this->range = $range;

        return $this;
    }

    /**
     * Generate values using the formula: y = YO - VO/k(i - e^kx)
     *
     * We handle situations where the number generated is greater than the max,
     * or less than the previous number generated.
     *
     * Can be generated for integers or floats.
     *
     * @param integer $size
     * @param boolean $integer
     * @return array
     */
    public function generateValues(int $size, bool $integer = false): array {
        $this->previousY = 0;
        $numbers = [];

        for ($x = 0; $x < $size; $x++) {
            $y = $this->calculateY($x, $size);

            // Handle integer generation
            if ($integer) {
                $y = ceil($y); // Round up to the nearest integer
            }

            // Handle boundary conditions
            if (!empty($numbers)) {
                if ($y > $this->max) {
                    $y = $this->max; // Cap the value at the maximum allowed
                }
                if ($y < $numbers[count($numbers) - 1]) {
                    $y = $numbers[count($numbers) - 1] + 0.02; // Ensure increasing sequence
                }
            }

            $numbers[] = $y;
        }

        // Ensure the last value is not lower than the second last value
        if ($integer && $size > 1 && $numbers[$size - 2] > $numbers[$size - 1]) {
            $numbers[$size - 1] = ($numbers[$size - 2] + $numbers[$size - 1]) / 2;
        }

        return $numbers;
    }

    /**
     * calculate the number.
     *
     * @param integer $x
     * @param integer $size
     * @return integer|float
     */
    protected function calculateY(int $x, int $size): int|float {
        $growthRate = pow($this->max / $this->min, 1 / ($size - 1));

        if ($x >= $size / 2) {
            $growthRate *= 1.0102;
        }

        $y = $this->min * pow($growthRate, $x);

        if ($x === $size - 1) {
            $y = $this->max;
        } elseif ($y > ($this->max - $this->range)) {
            if ($this->previousY === 0) {
                $this->previousY = $this->max - $this->range;
            } else {
                $this->previousY += $this->increase;
            }

            $y = $this->previousY + $this->increase;
        }



        return $y;
    }
}
