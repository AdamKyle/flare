<?php

// When an item is sold or bought:
Broadcast::channel('slot-timeout-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
