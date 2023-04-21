<?php

// When an item is sold or bought:
Broadcast::channel('update-shop-inventory-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

// When the crafting bar should show
Broadcast::channel('show-crafting-timeout-bar-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When the adventure logs are updated.
Broadcast::channel('update-adventure-logs-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When the notification is sent out.
Broadcast::channel('update-notifications-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When the market is updated.
Broadcast::channel('update-market', function($user) {
	return $user;
});

// When a user settles a kingdom, we want to show it for them only, on their map.
Broadcast::channel('add-kingdom-to-map-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When the kingdom updates
Broadcast::channel('update-kingdom-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When the units in movement are updated.
Broadcast::channel('update-units-in-movement-{userId}', function($user, $userId) {
   return $user->id === (int) $userId;
});

// When the user does too many map-actions.
Broadcast::channel('global-timeout-{userId}', function($user, $userId) {
    return $user->id === (int) $userId;
});

// When a user is timed out and they refresh or do some other action that they cannot.
Broadcast::channel('open-timeout-modal-{userId}', function($user, $userId) {
    return $user->id === (int) $userId;
});

// When an NPC Wants a components to show.
Broadcast::channel('component-show-{userId}', function($user, $userId) {
    return $user->id === (int) $userId;
});

// When a characters boons update.
Broadcast::channel('update-boons-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a characters Inventory updates
Broadcast::channel('update-inventory-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a characters Inventory Details updates
Broadcast::channel('update-inventory-details-{userId}', function ($user, $userId) {
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



