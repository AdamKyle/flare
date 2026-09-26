<?php

namespace App\Game\Skills\Values;

enum SkillBonusItemSource: string
{
    case EQUIPPED = 'equipped';
    case QUEST = 'quest';
}
