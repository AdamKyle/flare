<?php

Route::group(['middleware' => ['is.character.who.they.say.they.are', 'is.character.exploring', 'is.character.dead']], function () {
    Route::get('/gem-comparison/{character}', ['uses' => 'Api\GemComparisonController@compareGems']);
});

Route::group(['middleware' => ['is.character.who.they.say.they.are']], function () {
    Route::get('/socketed-gems/{character}/{item}', ['uses' => 'Api\AttachedGemsController@getGemsFromItem']);
});
