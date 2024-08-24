<?php

Route::group(['middleware' => [
    'auth',
    'is.character.who.they.say.they.are',
    'is.character.dead',
]], function () {
    Route::get('/game/completed-guide-quests/{user}', ['as' => 'completed.guide-quests', 'uses' => 'GuideQuestsController@index']);
    Route::get('/game/completed-guide-quest/{character}/{guideQuest}', ['as' => 'completed.guide-quest', 'uses' => 'GuideQuestsController@show']);
});
