<?php

namespace App\Game\Maps\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class MoveTimeOutEvent
{
    use SerializesModels;

    /**
     * @var Character $character
     */
    public Character $character;

    /**
     * @var int $timeout
     */
    public int $timeOut;

    /**
     * @var bool $setSail
     */
    public bool $setSail;

    /**
     * @var bool $traverse
     */
    public bool $traverse;

    /**
     * Constructor
     *
     * @param Character $character
     * @param int $timeOut | 0
     * @param bool $setSail | false
     * @param bool $traverse
     */
    public function __construct(Character $character, int $timeOut = 0, bool $setSail = false, bool $traverse = false) {
        $this->timeOut     = $timeOut;
        $this->character   = $character;
        $this->setSail     = $setSail;
        $this->traverse    = $traverse;
    }
}
