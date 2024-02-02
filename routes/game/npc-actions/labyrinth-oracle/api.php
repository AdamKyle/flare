<?php

Route::group(['middleware' => [
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'is.character.exploring']
], function() {
    Route::get('/character/{character}/inventory', ['uses' => 'Api\LabyrinthOracleController@inventoryItems']);
    Route::get('/character/{character}/transfer', ['uses' => 'Api\LabyrinthOracleController@transferItem']);
});
