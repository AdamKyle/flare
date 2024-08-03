<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;

class MoveTimeOutEvent
{
    use SerializesModels;

    public Character $character;

    public int $timeOut;

    public bool $setSail;

    public bool $traverse;

    /**
     * Constructor
     *
     * @param  int  $timeOut  | 0
     * @param  bool  $setSail  | false
     */
    public function __construct(Character $character, int $timeOut = 0, bool $setSail = false, bool $traverse = false)
    {
        $this->timeOut = $timeOut;
        $this->character = $character;
        $this->setSail = $setSail;
        $this->traverse = $traverse;
    }
}
