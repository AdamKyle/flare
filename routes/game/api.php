<?php

Route::post('/character-timeout', ['uses' => 'Api\CharacterSheetController@globalTimeout']);

Route::group(['middleware' => ['auth', 'throttle:100,1', 'is.character.who.they.say.they.are']], function() {
    Route::get('/character-sheet/{character}', ['uses' => 'Api\CharacterSheetController@sheet']);
    Route::get('/character-sheet/{character}/active-boons', ['uses' => 'Api\CharacterSheetController@activeBoons']);
    Route::get('/character-location-data/{character}', ['uses' => 'Api\CharacterSheetController@basicLocationInformation']);
    Route::post('/character-sheet/{character}/name-change', ['uses' => 'Api\CharacterSheetController@nameChange']);
    Route::post('/character-sheet/{character}/remove-boon/{boon}', ['uses' => 'Api\CharacterSheetController@cancelBoon']);

    Route::get('/notifications', ['uses' => 'Api\NotificationsController@index']);
    Route::post('/notifications/clear', ['uses' => 'Api\NotificationsController@clear']);
    Route::post('/notifications/{notification}/clear', ['uses' => 'Api\NotificationsController@clearNotification']);
    Route::get('/maps/{character}', ['uses' => 'Api\MapsController@index']);

    Route::get('/character/{character}/inventory', ['uses' => 'Api\CharacterInventoryController@inventory']);

    Route::group(['middleware' => ['is.character.dead', 'is.character.adventuring']], function() {
        Route::post('/character/{character}/inventory/destroy', ['uses' => 'Api\CharacterInventoryController@destroy']);
        Route::post('/character/{character}/inventory/destroy-all', ['uses' => 'Api\CharacterInventoryController@destroyAll']);
        Route::post('/character/{character}/inventory/disenchant-all', ['uses' => 'Api\CharacterInventoryController@disenchantAll']);
        Route::post('/character/{character}/inventory/move-to-set', ['uses' => 'Api\CharacterInventoryController@moveToSet']);
    });
});
