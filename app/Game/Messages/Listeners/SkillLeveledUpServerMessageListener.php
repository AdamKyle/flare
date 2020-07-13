<?php

namespace App\Game\Messages\Listeners;

use App\Game\Messages\Events\ServerMessageEvent as ServerMessage;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Flare\Events\SkillLeveledUpServerMessageEvent;

class SkillLeveledUpServerMessageListener
{

    private $serverMessage;

    public function __construct(ServerMessageBuilder $serverMessage) {

        $this->serverMessage = $serverMessage;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Flare\Events\CreateCharacterEvent  $event
     * @return void
     */
    public function handle(SkillLeveledUpServerMessageEvent $event)
    {

        $message =  'Skill: ' . $event->skill->name . ' is now level: ' . $event->skill->level . '!'; 

        return broadcast(new ServerMessage($event->user, $message));
    }
}
