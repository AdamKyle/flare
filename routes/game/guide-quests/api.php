<?php

Route::group(['middleware' => ['auth', 'throttle:100,1', 'is.character.who.they.say.they.are']], function() {
    Route::get('/character/guide-quest/{user}', ['uses' => 'Api\GuideQuestsController@getCurrentQuest']);
});
