<?php

// When setting the move time out bar.
Broadcast::channel('show-timeout-move-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

// When updating the map for a user.
Broadcast::channel('update-map-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});
