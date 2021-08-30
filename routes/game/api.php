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
});
