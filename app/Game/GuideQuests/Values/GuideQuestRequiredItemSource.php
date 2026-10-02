<?php

namespace App\Game\GuideQuests\Values;

enum GuideQuestRequiredItemSource: string
{
    case INVENTORY = 'inventory';
    case ALCHEMY_BAG = 'alchemy_bag';
}
