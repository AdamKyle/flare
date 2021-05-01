<?php

// When an item is sold or bought:
Broadcast::channel('update-shop-inventory-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

// When the crafting bar should show
Broadcast::channel('show-crafting-timeout-bar-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When the adventure logs are updated.
Broadcast::channel('update-adventure-logs-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When the notification is sent out.
Broadcast::channel('update-notifications-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When the market is updated.
Broadcast::channel('update-market', function($user) {
	return $user;
});

// When a user settles a kingdom, we want to show it for them only, on their map.
Broadcast::channel('add-kingdom-to-map-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When the kingdom updates
Broadcast::channel('update-kingdom-{userId}', function($user, $userId) {
	return $user->id === (int) $userId;
});

// When the units in movement are updated.
Broadcast::channel('update-units-in-movement-{userId}', function($user, $userId) {
   return $user->id === (int) $userId;
});
