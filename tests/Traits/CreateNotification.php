<?php

namespace Tests\Traits;

use App\Flare\Models\Notification;

trait CreateNotification {

    public function createNotification(array $options = []): Notification {
        return factory(Notification::class)->create($options);
    }

    public function createNotifications(array $options = [], int $amount = 1) {
        return factory(Notification::class, $amount)->create($options);
    }
}
