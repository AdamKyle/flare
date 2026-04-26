<?php

namespace App\Game\Automation\Values;

enum DelveOutcome: string
{
    case SURVIVED = 'survived';
    case TIMEOUT = 'timeout';
    case ERROR = 'error';
    case DIED = 'died';
}
