
<?php

// When the automation attack timer is called upon
Broadcast::channel('automation-attack-timeout-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});