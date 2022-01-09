<?php

// When a passive skill is training
Broadcast::channel('update-passive-skill-timer-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});