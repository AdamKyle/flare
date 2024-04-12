<?php

namespace App\Game\Messages\Listeners;

use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent as ServerMessage;

class SkillLeveledUpServerMessageListener
{
    /**
     * Handle the event.
     *
     * @param  \App\Game\Character\CharacterCreation\Events\CreateCharacterEvent  $event
     * @return void
     */
    public function handle(SkillLeveledUpServerMessageEvent $event)
    {

        $message =  'Skill: ' . $event->skill->name . ' is now level: ' . $event->skill->level . '!';

        return broadcast(new ServerMessage($event->user, $message));
    }
}
