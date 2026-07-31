<?php

namespace App\Game\Core\Traits;

use Illuminate\Support\Facades\Log;
use Throwable;

trait SafelyBroadcastsEvents
{
    protected function safelyDispatchBroadcastEvent(object $event, array $context = []): bool
    {
        try {
            event($event);

            return true;
        } catch (Throwable $throwable) {
            Log::warning('Non-critical broadcast event failed.', array_merge([
                'event_class' => $event::class,
                'exception_class' => $throwable::class,
                'exception' => $throwable->getMessage(),
            ], $context));

            return false;
        }
    }
}
