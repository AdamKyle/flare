<?php

Route::group(['middleware' => 'throttle:100,1'], function() {
    Route::get('/character-sheet/{character}', ['uses' => 'Api\CharacterSheetController@sheet']);
    Route::get('/character-location-data/{character}', ['uses' => 'Api\CharacterSheetController@basicLocationInformation']);
    Route::post('/character-sheet/{character}/name-change', ['uses' => 'Api\CharacterSheetController@nameChange']);

    Route::get('/notifications', ['uses' => 'Api\NotificationsController@index']);
    Route::post('/notifications/clear', ['uses' => 'Api\NotificationsController@clear']);
    Route::post('/notifications/{notification}/clear', ['uses' => 'Api\NotificationsController@clearNotification']);

    Route::get('/market-board/items', ['uses' => 'Api\MarketBoardController@index']);
    Route::get('/market-board/{item}/listing-details', ['uses' => 'Api\MarketBoardController@fetchItemDetails']);
    Route::get('/market-board/history', ['uses' => 'Api\MarketBoardController@history']);
    Route::post('/market-board/purchase/{character}', ['uses' => 'Api\MarketBoardController@purchase']);

    Route::get('/maps/{character}', ['uses' => 'Api\MapsController@index']);
});
