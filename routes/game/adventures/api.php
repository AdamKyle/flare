<?php

Route::middleware(['auth:api', 'is.player.banned'])->group(function() {

    // Adventure:
    Route::post('/character/{character}/adventure/{adventure}', ['uses' => 'Api\AdventureController@adventure']);

    // Cancel Adventure:
    Route::post('/character/{character}/adventure/{adventure}/cancel', ['uses' => 'Api\AdventureController@cancelAdventure']);

    // See adventure logs
    Route::get('/character/adventure/logs', ['uses' => 'Api\AdventureController@getLogs']);
});
