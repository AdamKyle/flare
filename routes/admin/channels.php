<?php

// When a user is banned:
Broadcast::channel('refresh-listener-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

// When a user is forced to change their name.
Broadcast::channel('force-name-change-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});