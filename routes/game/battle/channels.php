
<?php

// When setting the attack time out bar.
Broadcast::channel('show-timeout-bar-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

// When the player levels up the top bar should reflect that.
Broadcast::channel('update-top-bar-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});
