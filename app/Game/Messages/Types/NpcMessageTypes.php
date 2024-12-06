<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum NpcMessageTypes: string implements BaseMessageType
{
    case SEER_ACTIONS = 'seer_actions';
    case PAID_CONJURING = 'paid_conjuring';
    case ALREADY_CONJURED = 'already_conjured';
    case PUBLIC_CONJURATION_EXISTS = 'public_conjuration_exists';
    case LOCATION_OF_CONJURE = 'location_of_conjure';
    case CANT_AFFORD_CONJURATION = 'cant_afford_conjuration';
    case DEAD = 'dead';
    case GIVE_ITEM = 'give_item';
    case CURRENCY_GIVEN = 'currency_given';
    case SKILL_UNLOCKED = 'skill_unlocked';

    public function getValue(): string
    {
        return $this->value;
    }
}
