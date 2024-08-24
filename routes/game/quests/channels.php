<?php

// when unlocking alchemy
Broadcast::channel('unlock-skill-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// Updating Quests
Broadcast::channel('update-quests', function ($user) {
    return $user;
});

// Updating Raid Quests
Broadcast::channel('update-raid-quests', function ($user) {
    return $user;
});
