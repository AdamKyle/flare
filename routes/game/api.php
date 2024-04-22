<?php
Route::group(['middleware' => ['auth']], function () {
    Route::get('/calendar/events', ['uses' => 'Api\EventCalendarController@loadEvents']);
});

Route::post('/character-timeout', ['uses' => 'Api\CharacterSheetController@globalTimeout']);

Route::group(['middleware' => ['is.character.who.they.say.they.are']], function () {
    Route::get('/update-character-timers/{character}', ['uses' => 'Api\TimersController@updateTimersForCharacter']);

    Route::get('/maps/{character}', ['uses' => 'Api\MapsController@index']);
});
