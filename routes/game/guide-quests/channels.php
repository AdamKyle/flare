<?php

// When the guide quest button should be hidden
Broadcast::channel('guide-quest-button-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the guide quest button should be hidden
Broadcast::channel('guide-quest-completed-toast-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
