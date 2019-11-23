<?php

// Public chat:
Broadcast::channel('chat', function ($user) {
	return $user;
});

// When generating a server message for that player.
Broadcast::channel('server-message-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

// When recieiving a private message.
Broadcast::channel('private-message-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});
