<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum ClassRanksMessageTypes: string implements BaseMessageType
{
    case XP_FOR_CLASS_RANKS = 'xp_for_class_ranks';
    case XP_FOR_CLASS_MASTERIES = 'xp_for_class_masteries';
    case XP_FOR_EQUIPPED_CLASS_SPECIALS = 'xp_for_equipped_class_specialties';

    public function getValue(): string
    {
        return $this->value;
    }
}
