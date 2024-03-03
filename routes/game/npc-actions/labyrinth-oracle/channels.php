<?php

// When the labyrinth oracle needs to be updated
Broadcast::channel('update-labyrinth-oracle-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
