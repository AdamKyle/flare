<?php

namespace App\Game\Messages\Builders;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Npc;
use App\Game\Messages\Types\Concerns\BaseMessageType;

class NpcServerMessageBuilder
{
    /**
     * Build the server message
     */
    public function build(BaseMessageType $type, Npc $npc, ?CelestialFight $celestialFight = null): string
    {
        return match ($type->getValue()) {
            'dead' => '"I don\'t deal with dead people. Resurrect, child."',
            'paid_conjuring' => $npc->real_name.' takes your currency and smiles: "Thank you, child. I shall begin the conjuration at once."',
            'already_conjured' => '"No, child! I have already conjured for you!"',
            'public_conjuration_exists' => '"No, child! Too many Celestial Entities wondering around can cause an unbalance, even The Creator can\'t fix!"',
            'location_of_conjure' => '"Child, I have conjured the portal, I have opened the gates! Here is the location (X/Y): '
                .$celestialFight->x_position.'/'
                .$celestialFight->y_position.' ('
                .$celestialFight->gameMapName().' Plane)"',
            'give_item' => '"Here child, take this! It might be of use to you!" (Check the help section under quest items to see what this does, or check your inventory and click on the item)',
            'currency_given' => '"I have payment for you, here take this!"',
            'skill_unlocked' => '"Child, I have done something magical! I have unlocked a skill for you!"',
            'cant_afford_conjuration' => '"Why do these poor people always come to me?"
                '.$npc->real_name.' is not pleased with your lack of funds. try again when you can afford to be so brave.',
            default => '',
        };
    }
}
