<?php

Route::group(['middleware' => ['is.character.who.they.say.they.are', 'is.character.dead', 'is.character.exploring']], function() {
    Route::get('/character/{character}/inventory/smiths-workbench', ['uses' => 'Api\HolyItemsController@index']);
    Route::post('/character/{character}/smithy-workbench/apply', ['uses' => 'Api\HolyItemsController@apply']);
});
