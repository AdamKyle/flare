<?php

Route::group(['middleware' => ['auth', 'throttle:100,1', 'is.character.who.they.say.they.are']], function () {
    Route::get('/api/quests/{character}/browse-options', ['uses' => 'Api\QuestsController@browseOptions']);

    Route::get('/api/quests/{character}/tree', ['uses' => 'Api\QuestsController@tree']);

    Route::get('/api/quests/{character}/detail/{quest}', ['uses' => 'Api\QuestsController@detail']);

    Route::get('/api/quests/{character}', ['uses' => 'Api\QuestsController@index']);

    Route::get('/api/quest/{quest}/{character}', ['uses' => 'Api\QuestsController@quest']);

    Route::middleware(['is.character.dead'])->group(function () {
        Route::post('/api/quest/{quest}/hand-in-quest/{character}', ['uses' => 'Api\QuestsController@handInQuest']);
    });
});
