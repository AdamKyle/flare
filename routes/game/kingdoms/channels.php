<?php

// When a kingdoms attack logs update.:
Broadcast::channel('update-new-kingdom-logs-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a kingdoms building expansion completes
Broadcast::channel('update-building-expansion-details-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a kingdoms queue updates
Broadcast::channel('refresh-kingdom-queues-{userId}', function($user, $userId) {
    return $user->id === (int) $userId;
});



