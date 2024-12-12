<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum ItemSkillXpMessageTypes: string implements BaseMessageType
{
    case XP_FOR_ITEM_SKILL = 'xp_for_item_skill';

    public function getValue(): string
    {
        return $this->value;
    }
}
