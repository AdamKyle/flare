<?php

namespace App\Admin\Items\Values;

enum ItemPresentationKind: string
{
    case EQUIPPABLE = 'equippable';
    case QUEST = 'quest';
    case USABLE = 'usable';
}
