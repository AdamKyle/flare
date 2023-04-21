<?php

namespace Tests\Traits;

use App\Flare\Models\Notification;

trait CreateNotification {

    public function createNotification(array $options = []): Notification {
        return Notification::factory()->create($options);
    }

    public function createNotifications(array $options = [], int $amount = 1) {
        return Notification::factory()->count($amount)->create($options);
    }
}
