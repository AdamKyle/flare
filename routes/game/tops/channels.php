<?php

Broadcast::channel('tops-character-leaderboard', function ($user) {
    return ! is_null($user);
});

Broadcast::channel('tops-character-inspection-{characterId}', function ($user) {
    return ! is_null($user);
});

Broadcast::channel('tops-exploration-leaderboard', function ($user) {
    return ! is_null($user);
});

Broadcast::channel('tops-delve-leaderboard', function ($user) {
    return ! is_null($user);
});

Broadcast::channel('tops-faction-loyalty-leaderboard', function ($user) {
    return ! is_null($user);
});

Broadcast::channel('tops-kingdom-leaderboard', function ($user) {
    return ! is_null($user);
});
