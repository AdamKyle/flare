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
            case 'cannot_have':
                return  '"Sorry, you can\'t have that."';
            case 'not_enough_gold':
                return '"I do not like dealing with poor people. You do not have the gold child!"';
            case 'conjure':
                return $npc->real_name . '\'s Eyes light up as magic races through the air. "It is done child!" he bellows and magic strikes the earth!';
            case 'take_a_look':
                return '"Why don\'t you take a look, and show me what you can afford my child."';
            case 'location':
                return '"Child! You must come to me to make the exchange. Find me at (x/y): ' . $npc->x_position . '/' . $npc->y_position . ' ('.$npc->gameMapName().' Plane). Message me again when you are here."';
            case 'dead':
                return '"I don\'t deal with dead people. Resurrect child."';
            case 'adventuring':
                return '"Child, you are adventuring. Come chat with me when you are NOT busy!"';
            case 'paid_conjuring':
                return  $npc->real_name . ' take your currency and smiles: "Thank you child. I shall begin the conjuration at once."';
            case 'already_conjured':
                return '"No child! I have already conjured for you!"';
            case 'public_exists':
                return '"No Child! Too many Celestial Entities wondering around can cause an unbalance, even The Creator can\'t fix!"';
            case 'location_of_conjure':
                return '"Child, I have conjured the portal, I have opened the gates! Here is the location (X/Y): '.$celestialFight->x_position.'/'.$celestialFight->y_position.' ('.$celestialFight->gameMapName().' Plane)"';
            case 'taken_item':
                return '"Child! You have an item I want! I shall take that. In return I shall give you something you always wanted!"';
            case 'given_item':
                return '"Here child, take this! It might be of use to you!" (Check the help section under quest items to see what this does, or check your inventory and click on the item)';
            case 'inventory_full':
                return '"I cannot take the item from you child! Your inventory is to full! Come back when you clean out some space."';
            case 'gold_capped':
                return '"Child! You are Gold capped! I cannot take the item from you. Come back when you have less gold!" (check help section under quests for how much gold is to be rewarded with what you have)';
            case 'gold_dust_capped':
                return '"Child! You are Gold Dust capped! I cannot take the item from you. Come back when you have less gold dust!" (check help section under quests for how much gold dust is to be rewarded with what you have)';
            case 'shard_capped':
                return '"Child! You are shard capped! I cannot take the item from you. Come back when you have less shards!" (check help section under quests for how many shards is to be rewarded with what you have)';
            case 'currency_given':
                return '"I have payment for you, here take this!"';
            case 'quest_complete':
                return '"Pleasure doing business with you child!"';
            case 'no_quests':
                return '"Sorry child, no work for you today!"';
            case 'no_skill':
                return '"Sorry child, I do not see a skill that needs unlocking."';
            case 'dont_own_skill':
                return  '"Sorry child, you don\'t seem to own the skill to be unlocked!" (Chances are if you are seeing this, it\'s a bug. Head to discord post in the bugs section, link at the top)';
            case 'xp_given':
                return '"Here child, take this for your hard work!"';
            case 'skill_unlocked':
                return '"Child, I have done something magical! I have unlocked a skill for you!"';
            default:
                return '';
        }
    }
}
