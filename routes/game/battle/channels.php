
<?php

// When setting the attack time out bar.
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

// When the actions needed to be updated.
Broadcast::channel('update-actions-{userId}', function ($user, $userId) {
   return $user->id === (int) $userId;
});

// When character attack stats are to be updated.
Broadcast::channel('update-character-attack-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When a character makes an attack on a celestial entity.
Broadcast::channel('update-celestial-fight-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// When the Celestial Fight Details Change:
Broadcast::channel('celestial-fight-changes', function ($user) {
    return $user;
});
