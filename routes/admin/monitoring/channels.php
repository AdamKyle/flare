<?php

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

Broadcast::channel('admin-monitoring-batch-crafting', function ($user) {
    return $user->hasRole('Admin');
});
