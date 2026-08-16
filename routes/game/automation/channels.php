<?php

// When the automation attack timer is called upon
Broadcast::channel('automation-timeout-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When determining the status of the automated attack status
Broadcast::channel('automation-status-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When automation is running update the list.
Broadcast::channel('automations-list-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// when the exploration log updates.
Broadcast::channel('automation-log-update-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
