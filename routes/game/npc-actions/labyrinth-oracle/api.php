<?php

Route::group(['middleware' => [
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'is.character.exploring'],
], function () {
    Route::get('/character/{character}/labyrinth-oracle', ['uses' => 'Api\LabyrinthOracleController@inventoryItems']);
    Route::post('/character/{character}/transfer-attributes', ['uses' => 'Api\LabyrinthOracleController@transferItem']);
});
