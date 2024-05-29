
<?php

// When Faction Loyalty Updates
Broadcast::channel('faction-loyalty-update-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});
