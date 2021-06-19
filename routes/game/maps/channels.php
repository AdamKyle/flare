<?php

// When updating the map for a user.
Broadcast::channel('update-map-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When a user is traveling to another plane.
Broadcast::channel('update-map-plane-{userId}', function($user, $userId) {
   return $user->id === (int) $userId;
});

// When the plane count of characters changes.
Broadcast::channel('global-character-count-plane', function($user) {
    return $user;
});

// When a kingdom is settled.
BroadCast::channel('global-map-update', function($user) {
   return $user;
});


// When the NPC Kingdoms update.
Broadcast::channel('npc-kingdoms-update', function($user) {
    return $user;
});

// When a enemy kingdoms morale gets updated.
broadCast::channel('enemy-kingdom-morale-update', function($user) {
    return $user;

});
