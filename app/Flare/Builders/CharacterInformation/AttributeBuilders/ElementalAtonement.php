<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;

use App\Flare\Models\Item;
use App\Game\Gems\Services\GemComparison;

class ElementalAtonement extends BaseAttribute {

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
     * Calculate the atonement values from all equipped items.
     *
     * @return array|null
     */
    public function calculateAtonement(): array|null {

        $atonements = [];

        if (is_null($this->inventory)) {
            return null;
        }

        foreach ($this->inventory as $slot) {

            $itemAtonements = $this->buildPossibleAtonementDataWithDefaultValuesForItem($slot->item);

            if (empty($itemAtonements)) {
                continue;
            }

            forEach($itemAtonements as $key => $value) {
                if (isset($atonements[$key])) {
                    $atonements[$key] += floatval($value);

                    continue;
                }

                $atonements[$key] = floatval($value);
            }
        }

        if (empty($atonements)) {
            return null;
        }

        $characterAtonements = [];

        foreach ($atonements as $atonement => $value) {
            $characterAtonements[] = [
                'name'  => $atonement,
                'total' => $value,
            ];
        }

        return $this->gemComparison->determineHighestValue(['atonements' => $characterAtonements]);
    }

    protected function buildPossibleAtonementDataWithDefaultValuesForItem(Item $item): array {
        $itemAtonement = $this->gemComparison->getElementAtonement($item)['atonements'];
        $atonementData = [];

        if (empty($itemAtonement)) {
            return [];
        }

        foreach ($itemAtonement as $value) {
            $atonementData[$value['name']] = $value['total'];
        }

        return $atonementData;
    }

}
