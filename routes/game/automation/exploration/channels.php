<?php

// When the automation needs to send attack messages.
Broadcast::channel('automation-attack-messages-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When determining the details of the automated attack
Broadcast::channel('automation-attack-details-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

Broadcast::channel('exploration-output-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

Broadcast::channel('exploration-warning-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
