<?php

namespace App\Game\Core\Events;

use App\Flare\Models\AdventureLog;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class CreateAdventureNotificationEvent
{
    use SerializesModels;

    /**
     * @var AdventureLog $adventureLog
     */
    public $adventureLog;

    /**
     * Constructor
     * 
     * @param AdventureLog $adventureLog
     * @return void
     */
    public function __construct(AdventureLog $adventureLog)
    {
        $this->adventureLog = $adventureLog;
    }
}
