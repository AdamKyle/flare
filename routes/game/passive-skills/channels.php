<?php

// When a passive skill is training
Broadcast::channel('update-passive-skill-timer-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the passive skills update:
Broadcast::channel('update-passive-skills-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
