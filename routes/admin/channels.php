<?php

// When an item is sold or bought:
Broadcast::channel('refresh-listener-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});