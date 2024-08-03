<?php

// When the crafting bar should show
Broadcast::channel('show-crafting-timeout-bar-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the notification is sent out.
Broadcast::channel('update-notifications-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the market is updated.
Broadcast::channel('update-market', function ($user) {
    return $user;
});

// When locations become corrupt.
Broadcast::channel('corrupt-locations', function ($user) {
    return $user;
});

// When a user settles a kingdom, we want to show it for them only, on their map.
Broadcast::channel('add-kingdom-to-map-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the kingdom updates
Broadcast::channel('update-kingdom-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the units in movement are updated.
Broadcast::channel('update-units-in-movement-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the user does too many map-actions.
Broadcast::channel('global-timeout-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a user is timed out and they refresh or do some other action that they cannot.
Broadcast::channel('open-timeout-modal-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When an NPC Wants a components to show.
Broadcast::channel('component-show-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the player levels up the top bar should reflect that.
Broadcast::channel('update-top-bar-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the player levels up the top bar should reflect that.
Broadcast::channel('update-currencies-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the characters factions update.
Broadcast::channel('update-factions-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the character attacks update.
Broadcast::channel('update-character-attacks-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the character pvp status changes (they were attacked)
Broadcast::channel('update-pvp-attack-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the character pvp status changes (they were attacked - status messages and so on.)
Broadcast::channel('update-pvp-info-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
