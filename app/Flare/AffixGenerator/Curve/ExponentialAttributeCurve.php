<?php

namespace App\Flare\AffixGenerator\Curve;

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
     * We handle situations where the number generated is greator the the max,
     * or less then the previous number generated.
     * 
     * Can generated for integers or floats.
     *
     * @param integer $size
     * @param boolean $integer
     * @return array
     */
    public function generateValues(int $size, bool $integer = false): array {

        $this->previousY = 0;

        $numbers = array();
    
        for ($x = 0; $x < $size; $x++) {
            $y = $this->calculateY($x, $size);

            if ($integer) {
                if ($y > 10000) {
                    $y = round(ceil($y), -2);
                }

                if (!empty($numbers)) {
                    if ($numbers[count($numbers) - 1] > $y) {
                        $y = (($this->max - $numbers[count($numbers) - 1]) / 2) + $numbers[count($numbers) - 1];
                    }
                }

                $y = ceil($y);

                if ($y > $this->max) {
                    $y = $this->max;
                }

                if ($y === $this->max) {
                    $y = $numbers[count($numbers) - 1] + ($numbers[count($numbers) - 1] / 10);

                    if ($y > $this->max && $x !== $size - 1) {
                        $y = $numbers[count($numbers) - 1] + ($numbers[count($numbers) - 1] / 100);
                    }

                    if ($y > $this->max && $x === $size - 1) {
                        $y = $this->max;
                    }
                }
            }

            if (!$integer) {

                if (!empty($numbers) && $y > $this->max) {
                    $y = $numbers[count($numbers) - 1] + 0.02;                  
                }

                if (!empty($numbers) && $y < $numbers[count($numbers) - 1]) {
                    $y = $numbers[count($numbers) - 1] + 0.02;                  
                }
            }

            $numbers[] = $y;
        }
    
        if ($integer) {
            if ($numbers[$size - 2] > $numbers[$size - 1]) {
                $numbers[$size - 2] = ($numbers[$size - 3] + $numbers[$size - 1]) / 2;
            }
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