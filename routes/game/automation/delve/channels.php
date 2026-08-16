<?php

Broadcast::channel('delve-status-updated-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
