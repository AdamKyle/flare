<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum KingdomMessageTypes: string implements BaseMessageType
{
    case BUILDING_REPAIR_FINISHED = 'building_repair_finished';
    case BUILDING_UPGRADE_FINISHED = 'building_upgrade_finished';
    case NEW_BUILDING = 'new_building';
    case KINGDOM_RESOUCE_UPDATE = 'kingdom_resources_update';
    case UNIT_RECRUITMENT_FINISHED = 'unit_recruitment_finished';

    public function getValue(): string
    {
        return $this->value;
    }
}
