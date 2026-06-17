<?php

namespace App\Game\Core\Traits;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Log;

trait SafelyBroadcastsEvents
{
    protected function safelyDispatchBroadcastEvent(object $event, array $context = []): void
    {
        try {
            event($event);
        } catch (BroadcastException $throwable) {
            Log::warning('Non-critical broadcast event failed.', array_merge([
                'event_class' => $event::class,
                'exception_class' => $throwable::class,
                'exception' => $throwable->getMessage(),
            ], $context));
        }
    }
}
