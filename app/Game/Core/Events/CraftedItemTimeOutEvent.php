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
    public Character $character;

    /**
     * @var string|null $extraTime
     */
    public ?string $extraTime = null;

    /**
     * @var int|null $setTime
     */
    public ?int $setTime = null;

    /**
     * Constructor
     *
     * @param Character $character
     * @param string|null $extraTime | null
     * @param int|null $setTime
     */
    public function __construct(Character $character, string $extraTime = null, int $setTime = null) {
        $this->character = $character;
        $this->extraTime = $extraTime;
        $this->setTime   = $setTime;
    }
}
