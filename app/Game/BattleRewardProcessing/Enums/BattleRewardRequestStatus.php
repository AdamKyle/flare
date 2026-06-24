<?php

namespace App\Game\BattleRewardProcessing\Enums;

enum BattleRewardRequestStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
