<?php

Route::group(['middleware' => ['auth', 'throttle:100,1', 'is.character.who.they.say.they.are']], function() {
    Route::get('/api/quests/{character}', ['uses' => 'Api\QuestsController@index']);

    Route::get('/api/quest/{quest}/{character}', ['uses' => 'Api\QuestsController@quest']);
});
