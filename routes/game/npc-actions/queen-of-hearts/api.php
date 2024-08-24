<?php

Route::group(['middleware' => ['is.character.who.they.say.they.are', 'is.character.dead', 'is.character.exploring']], function () {
    Route::get('/character/{character}/inventory/uniques', ['uses' => 'Api\QueenOfHeartsController@uniquesOnly']);
    Route::post('/character/{character}/random-enchant/purchase', ['uses' => 'Api\QueenOfHeartsController@purchase']);
    Route::post('/character/{character}/random-enchant/reroll', ['uses' => 'Api\QueenOfHeartsController@reRoll']);
    Route::post('/character/{character}/random-enchant/move', ['uses' => 'Api\QueenOfHeartsController@moveAffixes']);
});
