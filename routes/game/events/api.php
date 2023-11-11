<?php

Route::middleware([
    'auth',
    'is.player.banned',
])->group(function () {

    Route::get('/global-event-goals', ['uses' => 'Api\EventGoalsController@getGlobalEventGoal']);
});
