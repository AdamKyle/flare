<?php

// When the shop should update
Broadcast::channel('update-shop-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
