
<?php

// When the automation attack timer is called upon
Broadcast::channel('automation-attack-timeout-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the automation needs to send attack messages.
Broadcast::channel('automation-attack-messages-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When determining the status of the automated attack status
Broadcast::channel('attack-automation-status-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When determining the details of the automated attack
Broadcast::channel('automation-attack-details-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});