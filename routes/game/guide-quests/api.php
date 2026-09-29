<?php

Route::group(['middleware' => ['auth', 'throttle:100,1', 'is.character.who.they.say.they.are']], function () {
    Route::get('/character/guide-quest/{user}', ['uses' => 'Api\GuideQuestsController@getCurrentQuest']);
    Route::post('/character/guide-quest/{user}/acknowledge-attention', ['uses' => 'Api\GuideQuestsController@acknowledgeAttention']);
    Route::get('/character/{user}/guide-quests/{guideQuest}/walkthrough', ['uses' => 'Api\GuideQuestWalkthroughController@show']);
    Route::put('/character/{user}/guide-quests/{guideQuest}/walkthrough', ['uses' => 'Api\GuideQuestWalkthroughController@update']);

    Route::group(['middleware' => ['is.character.exploring']], function () {
        Route::post('/guide-quests/hand-in/{user}/{guideQuest}', ['uses' => 'Api\GuideQuestsController@handInQuest']);
    });
});
