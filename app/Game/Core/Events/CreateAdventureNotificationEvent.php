<?php

namespace App\Game\Core\Events;

use App\Flare\Models\AdventureLog;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class CreateAdventureNotificationEvent
{
    use SerializesModels;

    public $adventureLog;

    public function __construct(AdventureLog $adventureLog)
    {
        $this->adventureLog = $adventureLog;
    }
}
