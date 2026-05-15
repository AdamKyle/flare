<?php

namespace App\Game\Automation\Enums;

enum DelveOutcome: string
{
    case SURVIVED = 'survived';
    case TIMEOUT = 'timeout';
    case ERROR = 'error';
    case DIED = 'died';
}
