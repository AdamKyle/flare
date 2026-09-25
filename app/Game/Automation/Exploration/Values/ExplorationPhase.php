<?php

namespace App\Game\Automation\Exploration\Values;

enum ExplorationPhase: string
{
    case WAITING = 'waiting';
    case FIGHTING = 'fighting';
    case PROCESSING_REWARDS = 'processing_rewards';
    case WAITING_FOR_NEXT_ENCOUNTER = 'waiting_for_next_encounter';
    case ENDED = 'ended';
}
