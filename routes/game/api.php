<?php

Route::group(['middleware' => ['auth', 'update.player-activity']], function () {
    Route::get('/calendar/events', ['uses' => 'Api\EventCalendarController@loadEvents']);
});

Route::group(['middleware' => ['is.character.who.they.say.they.are', 'update.player-activity']], function () {
    Route::get('/update-character-timers/{character}', ['uses' => 'Api\TimersController@updateTimersForCharacter']);

    Route::get('/maps/{character}', ['uses' => 'Api\MapsController@index']);

    Route::post('/suggestions-and-bugs/{character}', ['uses' => 'Api\SuggestionsAndBugsController@submitEntry']);

    Route::post('/update-player-flags/turn-off-intro/{character}', ['uses' => 'Api\UpdateCharacterFlagsController@turnOffIntro']);
});

Route::group(['middleware' => ['auth', 'session.time.tracking']], function () {
    Route::post('/game-heart-beat', ['uses' => 'Api\GameHeartBeatController@heartBeat']);
});
