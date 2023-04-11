<?php

// When the queen of hearts panel needs to be updated.
Broadcast::channel('update-queen-of-hearts-panel-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
