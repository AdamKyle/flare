<?php

namespace App\Game\Maps\Adventure\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Flare\Models\Character;

class MoveTimeOutEvent
{
    use SerializesModels;

    public $character;

    public $timeout;

    public function __construct(Character $character, int $timeOut = 0)
    {
        $this->timeOut   = $timeOut;
        $this->character = $character;
    }
}
