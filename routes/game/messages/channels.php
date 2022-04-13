<?php

// Public chat:
Broadcast::channel('chat', function ($user) {
	return $user;
});

// When generating a server message for that player.
Broadcast::channel('server-message-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

// When receiving a private message.
Broadcast::channel('private-message-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});

// When a global message is sent out.
Broadcast::channel('global-message', function ($user) {
    return $user;
});
