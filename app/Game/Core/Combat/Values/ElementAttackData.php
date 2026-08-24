<?php

namespace App\Game\Core\Combat\Values;

class ElementAttackData
{
    /**
     * Get the highest elemental value.
     */
    public function getHighestElementDamage(array $elementData): float
    {
        $maxValue = 0;

        foreach ($elementData as $name => $item) {
            if (is_array($item)) {
                $value = $item[$name];

                if ($value > $maxValue) {
                    $maxValue = $value;
                }
            } elseif ($item > $maxValue) {
                $maxValue = $item;
            }
        }

        return $maxValue;
    }

    /**
     * Get the name of the highest element.
     */
    public function getHighestElementName(array $elementData, float $highestElementForAttack): string
    {
        foreach ($elementData as $name => $item) {

            if (is_array($item)) {
                if ($item[$name] == $highestElementForAttack) {
                    return $name;
                }
            } elseif ($item == $highestElementForAttack) {
                return $name;
            }
        }

        return 'UNKNOWN';
    }

    /**
     * Is the attacking element only going to do half damage?
     *
     * Example: Fire vs Water.
     */
    public function isHalfDamage(array $elementData, string $attackingElementName): bool
    {

        $name = $this->getHighestElementName($elementData, $this->getHighestElementDamage($elementData));

        if ($name === 'UNKNOWN') {
            return false;
        }

        return strtolower($this->resolveElementType($name)->halfDamageOpposite()->value) === strtolower($attackingElementName);
    }

    /**
     * Is the attacking element going to do double damage?
     *
     * Example: Water vs Fire
     */
    public function isDoubleDamage(array $elementData, string $attackingElementName): bool
    {
        $name = $this->getHighestElementName($elementData, $this->getHighestElementDamage($elementData));

        if ($name === 'UNKNOWN') {
            return false;
        }

        return strtolower($this->resolveElementType($name)->doubleDamageOpposite()->value) === strtolower($attackingElementName);
    }

    /**
     * Get an array that contains the highest element name and its value.
     */
    public function getHighestElementalValue(array $elementData): array
    {
        $highestValue = $this->getHighestElementDamage($elementData);
        $key = $this->getHighestElementName($elementData, $highestValue);

        return [
            substr($key, 0, strpos($key, '_')) => $highestValue,
        ];
    }

    /**
     * Resolve the given element name to its Core ElementType, case-insensitively.
     */
    private function resolveElementType(string $name): ElementType
    {
        foreach (ElementType::cases() as $elementType) {
            if (strtolower($elementType->value) === strtolower($name)) {
                return $elementType;
            }
        }

        throw new \Exception($name.' does not exist.');
    }
}
