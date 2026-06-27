<?php

namespace App\Game\BattleRewardProcessing\Enums;

enum BattleRewardStepStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case CHECKPOINTED = 'checkpointed';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case RESUMABLE = 'resumable';
}
