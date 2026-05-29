<?php

namespace App\Game\Automation\Enums;

enum AutomatedFightResultType: string
{
    case BOUNTY_COMPLETED = 'bounty_completed';
    case TRAINING_BATCH_COMPLETED = 'training_batch_completed';
    case BOUNTY_STALLED_RETRY = 'bounty_stalled_retry';
    case TRAINING_STALLED_RETRY = 'training_stalled_retry';
    case BOUNTY_STALLED_MAX_ATTEMPTS_REACHED = 'bounty_stalled_max_attempts_reached';
    case TRAINING_STALLED_MAX_ATTEMPTS_REACHED = 'training_stalled_max_attempts_reached';
    case DIED_TO_BOUNTY_STARTED_TRAINING = 'died_to_bounty_started_training';
    case DIED_DURING_TRAINING = 'died_during_training';
    case DIED_TO_BOUNTY_AFTER_TRAINING = 'died_to_bounty_after_training';
    case NO_TRAINING_MONSTER_FOUND = 'no_training_monster_found';
    case NOT_ENOUGH_HEALTH_OR_INVALID_STATE = 'not_enough_health_or_invalid_state';
    case INVALID_TASK = 'invalid_task';
    case MONSTER_NOT_FOUND = 'monster_not_found';
    case ERROR = 'error';
}
