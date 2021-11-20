<?php

// When a kingdoms attack logs update.:
Broadcast::channel('update-kingdom-logs-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});



