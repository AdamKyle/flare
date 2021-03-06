<?php

Route::middleware(['auth:api', 'is.player.banned'])->group(function() {
    // Map related info:
    Route::get('/map/{user}', ['uses' => 'Api\MapController@mapInformation']);

    // Map Movement:
    Route::get('/is-water/{character}', ['uses' => 'Api\MapController@iswater']);
    Route::post('/move/{character}', ['uses' => 'Api\MapController@move']);

    // Set Sail:
    Route::post('/map/set-sail/{location}/{character}', ['uses' => 'Api\MapController@setSail']);

    // Teleport the player:
    Route::post('/map/teleport/{character}', ['uses' => 'Api\MapController@teleport']);
});