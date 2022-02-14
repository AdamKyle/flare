<?php

Route::middleware(['auth', 'is.player.banned', 'is.character.who.they.say.they.are'])->group(function() {

    Route::middleware(['is.character.exploring'])->group(function(){
        // Adventure:
        Route::post('/character/{character}/adventure/{adventure}', ['uses' => 'Api\AdventureController@adventure']);

        // Cancel Adventure:
        Route::post('/character/{character}/adventure/{adventure}/cancel', ['uses' => 'Api\AdventureController@cancelAdventure']);
    });

    // See adventure logs
    Route::get('/character/adventure/logs', ['uses' => 'Api\AdventureController@getLogs']);
});
