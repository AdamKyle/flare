<?php

namespace App\Game\Events\Services\Concerns;

use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;

interface EventEnder
{
    /**
     * @param  EventType  $type
     * @return bool
     */
    public function supports(EventType $type): bool;

    /**
     * @param  EventType  $type
     * @param  ScheduledEvent  $scheduled
     * @param  Event  $current
     * @return void
     */
    public function end(EventType $type, ScheduledEvent $scheduled, Event $current): void;
}
