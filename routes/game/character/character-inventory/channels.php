<?php

// When a characters Inventory updates
Broadcast::channel('update-inventory-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a characters Inventory Details updates
Broadcast::channel('update-inventory-details-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a characters boons update.
Broadcast::channel('update-boons-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
