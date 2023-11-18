<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function () {

    Route::get('/global-event-goals/{character}', ['uses' => 'Api\EventGoalsController@getGlobalEventGoal']);
});
