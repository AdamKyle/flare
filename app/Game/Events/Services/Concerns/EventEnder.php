<?php

namespace App\Game\Events\Services\Concerns;

use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;

interface EventEnder
{
    public function supports(EventType $type): bool;

    public function end(EventType $type, ScheduledEvent $scheduled, Event $current): void;
}
