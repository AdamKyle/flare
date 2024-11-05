<?php

namespace App\Game\Core\Events;

use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;

class UpdateCharacterInventoryCountEvent
{
    use SerializesModels;

    /**
     * @var Character;
     */
    public $character;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
    }
}
