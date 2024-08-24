<?php

Route::group(['middleware' => ['is.character.who.they.say.they.are', 'is.character.dead']], function () {
    Route::get('/survey/{survey}', ['uses' => 'Api\SurveyController@fetchSurvey']);
    Route::post('/survey/submit/{survey}/{character}', ['uses' => 'Api\SurveyController@saveAnswers']);
});
