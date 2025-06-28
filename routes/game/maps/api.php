<?php

Route::middleware(['auth', 'is.player.banned', 'is.character.who.they.say.they.are'])->group(function () {

    Route::get('/map/traverse-maps', ['uses' => 'Api\MapController@traverseMaps']);

    // Map related info:
    Route::get('/map/{character}', ['uses' => 'Api\MapController@mapInformation']);

    Route::get('/map/update-character-location-actions/{character}', ['uses' => 'Api\MapController@updateLocationActions']);

    // Fetch Quests for map:
    Route::get('/map/quests/{character}', ['uses' => 'Api\MapController@fetchQuests']);

    Route::get('/map/teleport-coordinates/{character}', ['uses' => 'Api\MapController@fetchTeleportCoordinates']);

    Route::get('/map/location-details/{location}', ['uses' => 'Api\MapController@getLocationInformation']);

    Route::get('/map/location-droppable-items/{location}', ['uses' => 'Api\MapController@getLocationDroppableQuestItems']);

    Route::group(['middleware' => 'throttle:moving'], function () {

        // Map Movement:
        Route::post('/move/{character}', ['uses' => 'Api\MapController@move']);

        Route::middleware(['is.character.exploring'])->group(function () {
            // Set Sail:
            Route::post('/map/set-sail/{character}', ['uses' => 'Api\MapController@setSail']);

            // Teleport:
            Route::post('/map/teleport/{character}', ['uses' => 'Api\MapController@teleport']);

            // Traverse the player:
            Route::post('/map/traverse/{character}', ['uses' => 'Api\MapController@traverse']);
        });

    });
});
