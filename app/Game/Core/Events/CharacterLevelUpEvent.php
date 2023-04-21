<?php

namespace App\Game\Core\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class CharacterLevelUpEvent
{
    use SerializesModels;

    /**
     * @var Character $character
     */
    public $character;

    public $shouldUpdateCache;

    /**
     * Constructor
     *
     * @param Character $character
     */
    public function __construct(Character $character, bool $shouldUpdateCache = true)
    {
        $this->character         = $character;
        $this->shouldUpdateCache = $shouldUpdateCache;
    }
}
