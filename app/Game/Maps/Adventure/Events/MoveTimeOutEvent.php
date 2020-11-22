<?php

namespace App\Game\Maps\Adventure\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class MoveTimeOutEvent
{
    use SerializesModels;

    /**
     * @var Character $character
     */
    public $character;

    /**
     * @var int $timeout
     */
    public $timeout;

    /**
     * @var bool $setSail
     */
    public $setSail;

    /**
     * Constructor
     * 
     * @param Character $character
     * @param int $timeOut | 0
     * @param bool $setSail | false
     */
    public function __construct(Character $character, int $timeOut = 0, bool $setSail = false)
    {
        $this->timeOut   = $timeOut;
        $this->character = $character;
        $this->setSail   = $setSail;
    }
}
