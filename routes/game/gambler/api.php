<?php

Route::group(['middleware' => ['auth', 'throttle:100,1', 'is.character.who.they.say.they.are']], function () {
    Route::get('/character/gambler', ['uses' => 'Api\GamblerController@getSlots']);

    Route::group(['middleware' => ['is.character.dead']], function () {
        Route::post('/character/gambler/{character}/slot-machine', ['uses' => 'Api\GamblerController@rollSlots']);
    });
});
