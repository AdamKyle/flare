
<?php

// When setting the attack time out bar.
Broadcast::channel('show-timeout-bar-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});
