<?php

namespace App\Game\BattleRewardProcessing\Enums;

enum BattleRewardRequestSourceType: string
{
    case QUEST = 'quest';
    case GUIDE_QUEST = 'guide_quest';
    case RAID_QUEST = 'raid_quest';
    case BATTLE = 'battle';
    case EXPLORATION = 'exploration';
    case AUTOMATION = 'automation';
    case FUTURE = 'future';
}
