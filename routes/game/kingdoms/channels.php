<?php

// When a kingdoms attack logs update.:
Broadcast::channel('update-new-kingdom-logs-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});



