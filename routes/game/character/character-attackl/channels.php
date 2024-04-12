<?php

Broadcast::channel('update-character-attack-{userId}', function($user, $userId) {
    return $user->id === (int) $userId;
});
