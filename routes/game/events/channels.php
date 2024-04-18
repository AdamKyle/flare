<?php

// When a character current progression updates
Broadcast::channel('player-current-event-goal-progression-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When an item is sold or bought:
Broadcast::channel('update-event-goal-progress', function ($user) {
    return $user;
});


