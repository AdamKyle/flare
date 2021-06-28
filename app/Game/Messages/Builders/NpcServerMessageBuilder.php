<?php

namespace App\Game\Messages\Builders;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Npc;

class NpcServerMessageBuilder {

    /**
     * Build the server message
     *
     * @param string $type
     * @return string
     */
    public function build(string $type, Npc $npc, CelestialFight $celestialFight = null): string {
        switch ($type) {
            case 'took_kingdom':
                return $npc->name . ' smiles in your direction. "It\'s done!"';
            case 'cannot_have':
                return  '"Sorry, you can\'t have that."';
            case 'not_enough_gold':
                return '"I do not like dealing with poor people. You do not have the gold child!"';
            case 'conjure':
                return $npc->name . '\'s Eyes light up as magic races through the air. "It is done child!" he bellows and magic strikes the earth!';
            case 'take_a_look':
                return '"Why don\'t you take a look, and show me what you can afford my child."';
            case 'location':
                return '"Child! You must come to me to make the exchange. Find me at (x/y): ' . $npc->x_position . '/' . $npc->y_position . '. Message me again when you are here."';
            case 'dead':
                return '"I don\'t deal with dead people. Resurrect child."';
            case 'adventuring':
                return '"Child, you are adventuring. Come chat with me when you are NOT busy!"';
            case 'paid_conjuring':
                return  $npc->name . ' take your currency and smiles: "Thank you child. I shall begin the conjuration at once."';
            case 'already_conjured':
                return '"No child! I have already conjured for you!"';
            case 'public_exists':
                return '"No Child! Too many Celestial Entities wondering around can cause an unbalance, even The Creator can\'t fix!"';
            case 'location_of_conjure':
                return '"Child, I have conjured the portal, I have opened the gates! Here is the location (X/Y): '.$celestialFight->x_position.'/'.$celestialFight->y_position.'"';
            default:
                return '';
        }
    }
}
