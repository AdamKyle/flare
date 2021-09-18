<?php

// When a characters crafting list updates
Broadcast::channel('update-crafting-list-{userId}', function($user, $userId) {
    return $user->id === (int) $userId;
});

// When a characters enchanting list updates
Broadcast::channel('update-enchanting-list-{userId}', function($user, $userId) {
    return $user->id === (int) $userId;
});