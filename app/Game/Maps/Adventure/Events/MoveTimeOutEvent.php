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

    public $setSail;

    public function __construct(Character $character, int $timeOut = 0, bool $setSail = false)
    {
        $this->timeOut   = $timeOut;
        $this->character = $character;
        $this->setSail   = $setSail;
    }
}
