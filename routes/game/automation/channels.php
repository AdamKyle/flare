
<?php

// When the automation attack timer is called upon
Broadcast::channel('exploration-timeout-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the automation needs to send attack messages.
Broadcast::channel('exploration-attack-messages-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When determining the status of the automated attack status
Broadcast::channel('exploration-status-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When determining the details of the automated attack
Broadcast::channel('exploration-attack-details-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When automation is running update the list.
Broadcast::channel('automations-list-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
