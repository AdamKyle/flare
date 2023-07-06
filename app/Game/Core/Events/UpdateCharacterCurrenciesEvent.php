<?php

namespace App\Game\Core\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class UpdateCharacterCurrenciesEvent {

    use SerializesModels;

    /**
     * @var Character $character;
     */
    public $character;

    /**
     * Create a new event instance.
     *
     * @param  Character $character
     * @return void
     */
    public function __construct(Character $character) {
        $this->character = $character;
    }
}
