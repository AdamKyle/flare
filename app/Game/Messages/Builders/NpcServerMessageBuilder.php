<?php

namespace App\Game\Messages\Builders;

class NpcServerMessageBuilder {

    /**
     * Build the server message
     *
     * @param string $type
     * @return string
     */
    public function build(string $type, string $npcName): string {
        switch ($type) {
            case 'took_kingdom':
                return 'The ' . $npcName . ' smiles in your direction. "It\'s done!"';
            case 'cannot_have':
                return  '"Sorry, you can\'t have that."';
            default:
                return '';
        }
    }
}
