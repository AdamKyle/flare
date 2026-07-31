<?php

Broadcast::channel('batch-crafting-status-updated-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
