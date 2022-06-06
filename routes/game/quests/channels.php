<?php

// when unlocking alchemy
Broadcast::channel('unlock-skill-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});
