<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;

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

        $highestElementDamage = $this->getHighestElementDamage($atonements);
        
        return [
            'elemental_data' => $atonements,
            'highest_element' => [
                'name'   => $highestElementDamage <= 0 ? 'N/A' : $this->getHighestElementName($atonements, $highestElementDamage),
                'damage' => $highestElementDamage,
            ],
        ];
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
