<?php

Route::group(['middleware' => ['auth', 'throttle:100,1', 'is.character.who.they.say.they.are']], function() {
    Route::get('/character/guide-quest/{user}', ['uses' => 'Api\GuideQuestsController@getCurrentQuest']);

    Route::group(['middleware' => ['is.character.exploring']], function() {
        Route::post('/guide-quests/hand-in/{user}/{guideQuest}', ['uses' => 'Api\GuideQuestsController@handInQuest']);
    });
});
