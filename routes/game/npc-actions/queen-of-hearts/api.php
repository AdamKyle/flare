<?php

Route::group(['middleware' => ['auth', 'is.character.who.they.say.they.are', 'is.character.dead', 'is.character.exploring']], function () {
    Route::get('/character/{character}/inventory/uniques', ['uses' => 'Api\QueenOfHeartsController@uniquesOnly']);
    Route::get('/character/{character}/queen-of-hearts/unique-items', ['uses' => 'Api\QueenOfHeartsController@uniqueItems']);
    Route::get('/character/{character}/queen-of-hearts/destination-items', ['uses' => 'Api\QueenOfHeartsController@destinationItems']);
    Route::post('/character/{character}/random-enchant/reroll', ['uses' => 'Api\QueenOfHeartsController@reRoll']);
    Route::post('/character/{character}/random-enchant/move', ['uses' => 'Api\QueenOfHeartsController@moveAffixes']);
});
