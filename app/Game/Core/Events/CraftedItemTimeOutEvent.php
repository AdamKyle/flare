<?php

namespace App\Game\Core\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class CraftedItemTimeOutEvent
{
    use SerializesModels;

    /**
     * @var Character $character
     */
    public $character;

    /**
     * @var string $extraTime
     */
    public $extraTime;

    /**
     * Constructor
     * 
     * @param Character $character
     * @param string $extraTime | null
     * @return void
     */
    public function __construct(Character $character, string $extraTime = null)
    {
        $this->character = $character;
        $this->extraTime = $extraTime;
    }
}
