<?php

Route::middleware(['auth:api', 'is.player.banned'])->group(function() {
    
    // Adventure:
    Route::post('/character/{character}/adventure/{adventure}', ['uses' => 'Api\AdventureController@adventure']);
    Route::post('/character/{character}/adventure/{adventure}/cancel', ['uses' => 'Api\AdventureController@cancelAdventure']);
    Route::get('/character/adventure/logs', ['uses' => 'Api\AdventureController@getLogs']);
});
