<?php

// When updating the map for a user.
Broadcast::channel('update-map-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});