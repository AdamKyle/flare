<?php

Route::group(['middleware' => ['is.character.who.they.say.they.are']], function() {
    Route::group(['middleware' => ['is.character.dead', 'is.character.exploring']], function () {
        Route::get('/visit-seer-camp/{character}', ['uses' => 'Api\SeerCampController@visitCamp']);
        Route::get('/seer-camp/gems-to-remove/{character}', ['uses' => 'Api\SeerCampController@fetchItemsWithGems']);
        Route::post('/seer-camp/add-sockets/{character}', ['uses' => 'Api\SeerCampController@rollSockets']);
        Route::post('/seer-camp/add-gem/{character}', ['uses' => 'Api\SeerCampController@attachGemToItem']);
        Route::post('/seer-camp/replace-gem/{character}', ['uses' => 'Api\SeerCampController@replaceGemOnItem']);
        Route::post('/seer-camp/remove-gem/{character}', ['uses' => 'Api\SeerCampController@removeGemFromItem']);
        Route::post('/seer-camp/remove-all-gems/{character}/{inventorySlot}', ['uses' => 'Api\SeerCampController@removeAllGemsFromItem']);
    });
});
