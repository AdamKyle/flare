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
                return $npc->real_name . ' smiles in your direction. "It\'s done!"';
            case 'kingdom_time_out':
                return $npc->real_name . ' looks disappointed as he looks at the ground and finally states: "No! You abandoned your last kingdom. You can wait..."';
            case 'cannot_have':
                return '"Sorry, you can\'t have that."';
            case 'too_poor':
                return '"I despise peasants! I spit on the ground before you! Come back when you can afford such treasures!"';
            case 'not_enough_gold':
                return '"I do not like dealing with poor people. You do not have the gold, child!"';
            case 'conjure':
                return $npc->real_name . '\'s Eyes light up as magic races through the air. "It is done, child!" he bellows and magic strikes the earth!';
            case 'dead':
                return '"I don\'t deal with dead people. Resurrect, child."';
            case 'paid_conjuring':
                return $npc->real_name . ' takes your currency and smiles: "Thank you, child. I shall begin the conjuration at once."';
            case 'already_conjured':
                return '"No, child! I have already conjured for you!"';
            case 'missing_queen_item':
                return $npc->real_name . ' looks at you with a blank stare. You try again and she just refuses to talk to you or acknowledge you. Maybe you need a quest item? Something to do with: Queens Decision (Quest)???';
            case 'public_exists':
                return '"No, child! Too many Celestial Entities wondering around can cause an unbalance, even The Creator can\'t fix!"';
            case 'location_of_conjure':
                return '"Child, I have conjured the portal, I have opened the gates! Here is the location (X/Y): '.$celestialFight->x_position.'/'.$celestialFight->y_position.' ('.$celestialFight->gameMapName().' Plane)"';
            case 'given_item':
                return '"Here child, take this! It might be of use to you!" (Check the help section under quest items to see what this does, or check your inventory and click on the item)';
            case 'currency_given':
                return '"I have payment for you, here take this!"';
            case 'skill_unlocked':
                return '"Child, I have done something magical! I have unlocked a skill for you!"';
            case 'cant_afford_conjuring':
                return '"Why do these poor people always come to me?"
                ' . $npc->real_name . ' is not pleased with your lack of funds. try again when you can afford to be so brave.';
            default:
                return '';
        }
    }
}
