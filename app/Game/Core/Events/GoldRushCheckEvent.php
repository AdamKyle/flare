<?php

namespace App\Game\Core\Events;

use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;

class GoldRushCheckEvent
{
    use SerializesModels;

    public function __construct(public Character $character) {}
}
