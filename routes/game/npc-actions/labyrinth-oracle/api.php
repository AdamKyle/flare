<?php

Route::group(['middleware' => [
    'auth',
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'is.character.exploring'],
], function () {
    Route::get('/character/{character}/labyrinth-oracle', ['uses' => 'Api\LabyrinthOracleController@inventoryItems']);
    Route::get('/character/{character}/labyrinth-oracle/items', ['uses' => 'Api\LabyrinthOracleController@items']);
    Route::post('/character/{character}/transfer-attributes', ['uses' => 'Api\LabyrinthOracleController@transferItem']);
});
