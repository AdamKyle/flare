<?php

namespace App\Game\Messages\Builders;

use App\Flare\Models\Npc;

class NpcServerMessageBuilder {

    /**
     * Build the server message
     *
     * @param string $type
     * @return string
     */
    public function build(string $type, Npc $npc): string {
        switch ($type) {
            case 'took_kingdom':
                return 'The ' . $npc->name . ' smiles in your direction. "It\'s done!"';
            case 'cannot_have':
                return  '"Sorry, you can\'t have that."';
            case 'not_enough_gold':
                return '"I do not like dealing with poor people. You do not have the gold child!"';
            case 'conjure':
                return 'The ' . $npc->name . '\'s Eyes light up as magic races through the air. "It is done child!" he bellows and magic strikes the earth!';
            case 'take_a_look':
                return '"Why don\'t you take a look, and show me what you can afford my child."';
            case 'location':
                return '"Child! You must come to me to make the exchange. Come to me at (x/y): ' . $npc->x_position . '/' . $npc->y_position . '. Mess me again when you are here."';
            default:
                return '';
        }
    }
}
