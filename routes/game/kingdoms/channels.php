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

// When the kingdoms table should update.
Broadcast::channel('kingdoms-list-data-{userId}', function($user, $userId) {
    return $user->id === (int) $userId;
});

// When the kingdoms capital city building upgrade/repair table should update.
Broadcast::channel('update-kingdom-building-data-{userId}', function($user, $userId) {
    return $user->id === (int) $userId;
});



