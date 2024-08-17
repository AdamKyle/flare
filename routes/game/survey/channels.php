<?php

// When a characters crafting list updates
Broadcast::channel('show-survey-{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
