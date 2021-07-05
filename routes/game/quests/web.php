<?php

Route::group(['middleware' => [
    'auth',
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'is.character.adventuring',
]], function() {
    Route::get('/game/completed-quests/{user}', ['as' => 'completed.quests', 'uses' => 'QuestsController@index']);
    Route::get('/game/completed-quest/{character}/{questsCompleted}', ['as' => 'completed.quest', 'uses' => 'QuestsController@show']);
});
