<?php

namespace App\Flare\Traits;

use App\Game\Gems\Values\GemTypeValue;

trait ElementAttackData {

    /**
     * Get the highest elemental value.
     *
     * @param array $elementData
     * @return integer
     */
    public function getHighestElementDamage(array $elementData): float {
        $maxValue = 0;

        foreach ($elementData as $name => $item) {
            if (is_array($item)) {
                $value = floatval($item[$name]);

                if ($value > $maxValue) {
                    $maxValue = $value;
                }
            } else if ($item > $maxValue) {
                $maxValue = $item;
            }
        }

        return $maxValue;
    }

    /**
     * Get the name of teh highest element.
     *
     * @param array $elementData
     * @param integer $highestElementForAttack
     * @return string
     */
    public function getHighestElementName(array $elementData, float $highestElementForAttack): string {
        foreach ($elementData as $name => $item) {

            if (is_array($item)) {
                $innerValue = floatval($item[$name]);

                if ($innerValue === floatval($highestElementForAttack)) {
                    return $name;
                }
            } else if ($item === floatval($highestElementForAttack)) {
                return $name;
            }
        }

        return 'UNKNOWN';
    }

    /**
     * Is the attacking element only going to do half damage?
     *
     * Example: Fire vs Water.
     *
     * @param array $elementData
     * @param string $attackingElementName
     * @return boolean
     */
    public function isHalfDamage(array $elementData, string $attackingElementName): bool {

        $name = $this->getHighestElementName($elementData, $this->getHighestElementDamage($elementData));

        if ($name === 'UNKNOWN') {
            return false;
        }

        return GemTypeValue::getOppsiteForHalfDamage($name)  === $attackingElementName;
    }

    /**
     * Is the attacking element going to do double damage?
     *
     * Example: Water vs Fire
     *
     * @param array $elementData
     * @param string $attackingElementName
     * @return boolean
     */
    public function isDoubleDamage(array $elementData, string $attackingElementName): bool {
        $name = $this->getHighestElementName($elementData, $this->getHighestElementDamage($elementData));

        if ($name === 'UNKNOWN') {
            return false;
        }

        return GemTypeValue::getOppsiteForDoubleDamage($name)  === $attackingElementName;
    }

    /**
     * Get an array that contains the highest element name and its value.
     *
     * @param array $elementData
     * @return array
     */
    public function getHighestElementalValue(array $elementData): array {
        $highestValue = $this->getHighestElementDamage($elementData);
        $key          = $this->getHighestElementName($elementData, $highestValue);

        $result = [
            substr($key, 0, strpos($key, '_')) => $highestValue
        ];

        return $result;
    }
}
