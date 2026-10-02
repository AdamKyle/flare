<?php

namespace App\Game\GuideQuests\Values;

enum GuideQuestWalkthroughStatus: string
{
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
}
