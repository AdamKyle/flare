<?php

// When updating the map for a user.
Broadcast::channel('update-map-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When a user is traveling to another plane.
Broadcast::channel('update-map-plane-{userId}', function($user, $userId) {
   return $user->id === (int) $userId;
});
