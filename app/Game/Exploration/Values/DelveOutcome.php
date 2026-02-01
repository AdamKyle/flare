<?php

namespace App\Game\Exploration\Values;

enum DelveOutcome: string
{
    case SURVIVED = 'survived';
    case TIMEOUT = 'timeout';
    case ERROR = 'error';
    case DIED = 'died';
}
