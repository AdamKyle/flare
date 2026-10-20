<?php

namespace App\Game\Core\Events;

use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;

class UpdateTopBarEvent
{
    use SerializesModels;

    /**
     * @var Character;
     */
    public $character;

    public bool $updateCharacterTops;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Character $character, bool $updateCharacterTops = true)
    {
        $this->character = $character;
        $this->updateCharacterTops = $updateCharacterTops;
    }
}
