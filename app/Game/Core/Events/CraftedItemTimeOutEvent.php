<?php

namespace App\Game\Core\Events;

use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;

class CraftedItemTimeOutEvent
{
    use SerializesModels;

    public Character $character;

    public ?string $extraTime = null;

    public ?int $setTime = null;

    /**
     * Constructor
     *
     * @param  string|null  $extraTime  | null
     */
    public function __construct(Character $character, ?string $extraTime = null, ?int $setTime = null)
    {
        $this->character = $character;
        $this->extraTime = $extraTime;
        $this->setTime = $setTime;
    }
}
