<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;

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

            $itemAtonement = $this->gemComparison->getElementAtonement($slot->item)['atonements'];;

            if (empty($itemAtonement)) {
                continue;
            }

            if (empty($atonements)) {

                foreach ($itemAtonement as $value) {
                    $atonements[$value['name']] = $value['total'];
                }

                continue;
            }

            foreach ($itemAtonement as $atonement) {
                foreach ($atonements as $key => $value) {
                    if ($atonement['name'] === $key) {
                        $atonements[$key] += $atonement['total'];
                        continue;
                    }

                    $atonements[$atonement['name']] = $atonement['total'];
                }
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

}
