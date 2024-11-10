<?php

namespace App\Flare\ExponentialCurve\Curve;

class LinearAttributeCurve
{
    private float $min;
    private float $max;
    private float $increase;

    /**
     * Set minimum value
     *
     * @return LinearAttributeCurve
     */
    public function setMin(float $min): LinearAttributeCurve
    {
        $this->min = $min;
        return $this;
    }

    /**
     * Set maximum value
     *
     * @return LinearAttributeCurve
     */
    public function setMax(float $max): LinearAttributeCurve
    {
        $this->max = $max;
        return $this;
    }

    /**
     * Set the increase amount
     *
     * @param float $increase
     * @return LinearAttributeCurve
     */
    public function setIncrease(float $increase): LinearAttributeCurve
    {
        $this->increase = $increase;

        return $this;
    }

    /**
     * Generate values that increase linearly from min to max, over the given size.
     * The numbers will always increase and the decimals will progress smoothly.
     *
     * @param int $size The number of values to generate.
     * @return array The generated values.
     */
    public function generateValues(int $size): array
    {
        $numbers = [];
        $increment = ($this->max - $this->min) / ($size - 1); // Calculate the increment to reach max

        for ($i = 0; $i < $size; $i++) {
            // Calculate the value based on linear progression
            $value = $this->min + ($i * $increment);

            // Round to two decimal places to avoid repetitive decimals like 0.12, 0.123
            $value = round($value, 2);

            // Ensure the value always increases and that the decimal always increases
            if (!empty($numbers) && $value <= $numbers[count($numbers) - 1]) {
                $value = $numbers[count($numbers) - 1] + $this->increase; // Ensure the number always increases
            }

            // Ensure that the value does not exceed the max, but also avoids duplicates
            if ($value > $this->max) {
                // If the value exceeds the max, set it to the largest unique value just under the max
                $value = round($this->max - 0.01, 2);
            }

            // Add the value to the list
            $numbers[] = $value;
        }

        // Ensure the last value is exactly the max
        if ($numbers[$size - 1] !== $this->max) {
            $numbers[$size - 1] = $this->max;
        }

        return $numbers;
    }
}
