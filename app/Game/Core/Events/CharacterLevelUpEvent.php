<?php

namespace App\Game\Core\Events;

use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;

class CharacterLevelUpEvent
{
    use SerializesModels;

    /**
     * @var Character
     */
    public $character;

    public $shouldUpdateCache;

    /**
     * Constructor
     */
    public function __construct(Character $character, bool $shouldUpdateCache = true)
    {
        $this->character = $character;
        $this->shouldUpdateCache = $shouldUpdateCache;
    }
}
