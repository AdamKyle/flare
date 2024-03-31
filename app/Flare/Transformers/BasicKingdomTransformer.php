<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\Kingdom;

class BasicKingdomTransformer extends TransformerAbstract {

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * Set the character.
     *
     * @param Character $character
     * @return $this
     */
    public function setCharacter(Character $character): BasicKingdomTransformer {
        $this->character = $character;

        return $this;
    }

    /**
     * Gets the response data for the character sheet
     *
     * @param Kingdom $kingdom
     * @return array
     */
    public function transform(Kingdom $kingdom): array {
        return [
            'id'                 => $kingdom->id,
            'name'               => $kingdom->name,
            'max_stone'          => $kingdom->max_stone,
            'max_wood'           => $kingdom->max_wood,
            'max_clay'           => $kingdom->max_clay,
            'max_iron'           => $kingdom->max_iron,
            'current_stone'      => $kingdom->current_stone,
            'current_wood'       => $kingdom->current_wood,
            'current_clay'       => $kingdom->current_clay,
            'current_iron'       => $kingdom->current_iron,
            'current_population' => $kingdom->current_population,
            'max_population'     => $kingdom->max_population,
            'x_position'         => $kingdom->x_position,
            'y_position'         => $kingdom->y_position,
            'current_morale'     => $kingdom->current_morale,
            'max_morale'         => $kingdom->max_morale,
            'treasury'           => $kingdom->treasury,
            'gold_bars'          => $kingdom->gold_bars,
            'passive_defence'    => $kingdom->fetchDefenceBonusFromPassive(),
            'treasury_defence'   => $kingdom->fetchTreasuryDefenceBonus(),
            'walls_defence'      => $kingdom->getWallsDefence(),
            'gold_bars_defence'  => $kingdom->fetchGoldBarsDefenceBonus(),
            'defence_bonus'      => $kingdom->fetchKingdomDefenceBonus(),
            'item_resistance_bonus' => $kingdom->kingdomItemResistanceBonus(),
            'is_npc_owned'       => $kingdom->npc_owned,
            'is_enemy_kingdom'   => !$kingdom->npc_owned ? $kingdom->character_id !== $this->character->id : false,
            'is_protected'       => !is_null($kingdom->protected_until),
        ];
    }
}
