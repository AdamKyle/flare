<?php

Route::group(['middleware' => ['is.character.who.they.say.they.are', 'is.character.exploring', 'is.character.dead']], function() {
    Route::get('/gem-comparison/{character}', ['uses' => 'Api\GemComparisonController@compareGems']);
});
