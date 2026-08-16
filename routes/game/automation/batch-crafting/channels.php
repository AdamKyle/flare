<?php

Broadcast::channel('batch-crafting-status-updated-{userId}', function ($user, $userId) {
    return $user->getAuthIdentifier() === filter_var($userId, FILTER_VALIDATE_INT);
});
