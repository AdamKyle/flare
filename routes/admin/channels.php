<?php

// When a user is banned:
Broadcast::channel('refresh-listener-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a user is forced to change their name.
Broadcast::channel('force-name-change-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the admin message table needs to be updated.
Broadcast::channel('refresh-messages-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a user visits the site and registers or logs in or even logs out.
Broadcast::channel('update-admin-site-statistics-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

Broadcast::channel('admin-character-reward-queue', function ($user) {
    return $user->hasRole('Admin');
});

Broadcast::channel('admin-monitoring-exploration', function ($user) {
    return $user->hasRole('Admin');
});

Broadcast::channel('admin-monitoring-faction-loyalty', function ($user) {
    return $user->hasRole('Admin');
});

Broadcast::channel('admin-monitoring-delve', function ($user) {
    return $user->hasRole('Admin');
});
