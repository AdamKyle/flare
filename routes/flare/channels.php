<?php

// When a character updates, up their character sheet.
Broadcast::channel('update-character-sheet-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

Broadcast::channel('update-character-inventory-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

Broadcast::channel('update-character-attack-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

Broadcast::channel('update-event-schedule', function($user) {
    return $user;
});
