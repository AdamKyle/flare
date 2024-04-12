<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Flare\Models\Item;
use App\Flare\Traits\ElementAttackData;
use App\Game\Gems\Services\GemComparison;

class ElementalAtonement extends BaseAttribute {

    use ElementAttackData;

    /**
     * @var GemComparison
     */
    private GemComparison $gemComparison;

    /**
     * @param GemComparison $gemComparison
     */
    public function __construct(GemComparison $gemComparison) {
        $this->gemComparison = $gemComparison;
    }

    /**
     * Calculates the average atonements for items in the inventory.
     *
     * @return array|null An array of average atonement data or null if the inventory is empty.
     */
    public function calculateAtonement(): ?array {
        $atonements = $this->calculateAtonements();

        if (is_null($atonements)) {
            return null;
        }

        $averages = $this->calculateAverages($atonements);
        $highestElement = $this->calculateHighestElement($averages);

        return [
            'atonements' => $averages,
            'highest_element' => $highestElement,
        ];
    }

    /**
     * Calculates the atonements for items in the inventory.
     *
     * @return array|null An array of atonement data or null if the inventory is empty.
     */
    private function calculateAtonements(): ?array {
        if (is_null($this->inventory)) {
            return null;
        }

        $atonements = [];

        foreach ($this->inventory as $slot) {
            $itemAtonements = $this->buildPossibleAtonementDataWithDefaultValuesForItem($slot->item);

            if (!empty($itemAtonements)) {
                foreach ($itemAtonements as $key => $value) {
                    $atonements[$key][] = floatval($value);
                }
            }
        }

        return $atonements;
    }

    /**
     * Calculates the average values for each key in the given atonement data.
     *
     * - Caps at 75%.
     *
     * @param array $atonements The atonement data.
     * @return array The array of average values.
     */
    private function calculateAverages(array $atonements): array {
        $averages = [];

        foreach ($atonements as $key => $values) {
            $average = array_sum($values) / count($values);

            $averages[$key] = $average > 0.75 ? 0.75 : $average;
        }

        return $averages;
    }

    /**
     * Calculates the highest element based on the given atonement data.
     *
     * @param array $atonements The atonement data.
     *
     * @return array The highest element information.
     */
    private function calculateHighestElement(array $atonements): array {
        $highestElementDamage = $this->getHighestElementDamage($atonements);
        $highestElementName = ($highestElementDamage <= 0) ? 'N/A' : $this->getHighestElementName($atonements, $highestElementDamage);

        return [
            'name' => $highestElementName,
            'damage' => $highestElementDamage,
        ];
    }

    /**
     * Build possible Data with default values for an item
     *
     * @param Item $item
     * @return array
     */
    protected function buildPossibleAtonementDataWithDefaultValuesForItem(Item $item): array {
        return $this->gemComparison->getElementAtonement($item)['atonements'];
    }
}
