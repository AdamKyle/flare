<?php

Route::middleware(['auth', 'is.player.banned', 'is.character.who.they.say.they.are'])->group(function() {
    // Map related info:
    Route::get('/map/{user}', ['uses' => 'Api\MapController@mapInformation']);

    Route::group(['middleware' => 'throttle:moving'], function() {
        // Map Movement:
        Route::post('/move/{character}', ['uses' => 'Api\MapController@move']);

        Route::middleware(['character.attack.automation'])->group(function() {
            // Set Sail:
            Route::post('/map/set-sail/{location}/{character}', ['uses' => 'Api\MapController@setSail']);

            // Teleport:
            Route::post('/map/teleport/{character}', ['uses' => 'Api\MapController@teleport']);

            // Traverse the player:
            Route::post('/map/traverse/{character}', ['uses' => 'Api\MapController@traverse']);
        });

    });
});
