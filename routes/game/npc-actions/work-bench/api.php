<?php

Route::group(['middleware' => ['auth', 'is.character.who.they.say.they.are', 'is.character.dead', 'is.character.exploring']], function () {
    Route::get('/character/{character}/inventory/smiths-workbench', ['uses' => 'Api\HolyItemsController@index']);
    Route::get('/character/{character}/inventory/smiths-workbench/items', ['uses' => 'Api\HolyItemsController@items']);
    Route::get('/character/{character}/inventory/smiths-workbench/oils', ['uses' => 'Api\HolyItemsController@oils']);
    Route::post('/character/{character}/smithy-workbench/apply', ['uses' => 'Api\HolyItemsController@apply']);
});
