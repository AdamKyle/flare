
<?php

// When setting the attack timeout bar.
Broadcast::channel('show-timeout-bar-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

// When the player levels up the top bar should reflect that.
Broadcast::channel('update-top-bar-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

// When the character is dead
Broadcast::channel('character-is-dead-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

// When the map-actions needed to be updated.
Broadcast::channel('update-map-actions-{userId}', function ($user, $userId) {
   return $user->id === (int) $userId;
});

// When character attack stats are to be updated.
Broadcast::channel('update-character-attack-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When we update a characters base stats.
Broadcast::channel('update-character-base-stats-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a character makes an attack on a celestial entity.
Broadcast::channel('update-celestial-fight-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a character makes an attack on a celestial entity.
Broadcast::channel('update-character-celestial-timeout-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When we update the status of the character
Broadcast::channel('update-character-status-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the raid bosses health updates.
Broadcast::channel('update-raid-boss-health-attack', function ($user) {
    return $user;
});

// When the Celestial Fight Details Change:
Broadcast::channel('celestial-fight-changes', function ($user) {
    return $user;
});

// When a character revives
Broadcast::channel('character-revive-{userId}', function($user, $userId) {
    return $user->id === (int) $userId;
});

// Raid Attacks left
Broadcast::channel('update-raid-attacks-left-{userId}', function($user, $userId) {
    return $user->id === (int) $userId;
});
