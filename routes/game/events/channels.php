<?php

// When an item is sold or bought:
Broadcast::channel('update-event-goal-progress', function ($user) {
    return $user;
});
