<?php

// When setting the move timeout bar.
Broadcast::channel('show-timeout-move-{userId}', function ($user, $userId) {
	return $user->id === (int) $userId;
});
