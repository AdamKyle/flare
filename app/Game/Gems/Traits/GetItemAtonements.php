<?php

namespace App\Game\Gems\Traits;

use App\Flare\Models\Item;
use Illuminate\Support\Facades\DB;
use App\Game\Gems\Values\GemTypeValue;
use App\Flare\Traits\ElementAttackData;

trait GetItemAtonements {

    use ElementAttackData;

    /**
     * Get atonement data based on gem array data.
     *
     * @param array $gemData
     * @return array
     */
    public function getElementAtonementFromArray(array $gemData): array {

        $atonements = [
            'atonements'       => [],
            'elemental_damage' => [],
        ];

        foreach (GemTypeValue::getNames() as $type => $name) {
            if (empty($gemData)) {
                $atonements['atonements'][$name] = 0.0;
            } else {
                $atonements['atonements'][$name] = $this->fetchSummedValueFromArray($gemData, $type, $name);
            }
        }

        return $this->determineHighestValue($atonements);
    }

    /**
     * Get elemental atonement details from gems on item.
     *
     * @param Item $item
     * @return array
     */
    public function getElementAtonement(Item $item): array {
        $atonements = [
            'atonements'       => [],
            'elemental_damage' => [],
        ];

        foreach (GemTypeValue::getNames() as $type => $name) {
            if ($item->socket_count <= 0) {
                $atonements['atonements'][$name] = 0.0;
            } else {
                $atonements['atonements'][$name] = $this->fetchSummedValue($item, $type, $name);
            }
        }

        return $this->determineHighestValue($atonements);
    }

    /**
     * Sum the atonement types from an array of gem data.
     *
     * @param array $gemData
     * @param int $type
     * @param string $name
     * @return array
     */
    protected function fetchSummedValueFromArray(array $gemData, int $type, string $name): array {
        $result = [];

        foreach ($gemData as $gem) {
            $atonementType =
                $gem["primary_atonement_type"] == $type ? "primary_atonement_amount" : ($gem["secondary_atonement_type"] == $type ? "secondary_atonement_amount" : ($gem["tertiary_atonement_type"] == $type ? "tertiary_atonement_amount" : ""));

            if ($atonementType) {
                if (array_key_exists($name, $result)) {
                    $result[$name] += $gem[$atonementType];
                } else {
                    $result[$name] = $gem[$atonementType];
                }
            }
        }

        return array_map(function ($name, $total) {
            return [$name => $total];
        }, array_keys($result), $result)[0];
    }

    /**
     * Fetch summed values for type.
     *
     * @param Item $item
     * @param int $type
     * @param string $name
     * @return float
     */
    protected function fetchSummedValue(Item $item, int $type, string $name): float {
        $value = $item->sockets()->join('gems', function ($join) use ($type) {
            $join->on('item_sockets.gem_id', '=', 'gems.id')
                ->where(function ($query) use ($type) {
                    $query->where('gems.primary_atonement_type', '=', $type)
                        ->orWhere('gems.secondary_atonement_type', '=', $type)
                        ->orWhere('gems.tertiary_atonement_type', '=', $type);
                });
        })->sum(DB::raw("CASE
                    WHEN gems.primary_atonement_type = $type THEN gems.primary_atonement_amount
                    WHEN gems.secondary_atonement_type = $type THEN gems.secondary_atonement_amount
                    WHEN gems.tertiary_atonement_type = $type THEN gems.tertiary_atonement_amount
                    ELSE 0
                END"));

        return $value;
    }

    /**
     * Determine highest value.
     *
     * @param array $atonements
     * @return array
     */
    public function determineHighestValue(array $atonements): array {

        $elementData = $atonements['atonements'];

        $highestElementalDamage = $this->getHighestElementDamage($elementData);

        $highestElementalName   = $this->getHighestElementName($elementData, $highestElementalDamage);

        if ($highestElementalDamage <= 0) {
            $atonements['elemental_damage'] = [
                'name'   => 'N/A',
                'amount' => 0.0,
            ];

            return $atonements;
        }

        $atonements['elemental_damage'] = [
            'name'   => $highestElementalName,
            'amount' => $highestElementalDamage,
        ];

        return $atonements;
    }
}
