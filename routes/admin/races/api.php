<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/races', ['uses' => 'Api\RacesController@index']);
    Route::post('/admin/races', ['uses' => 'Api\RacesController@store']);
    Route::post('/admin/races/import', ['uses' => 'Api\RaceImportController']);
    Route::get('/admin/races/{gameRace}/edit', ['uses' => 'Api\RacesController@edit']);
    Route::put('/admin/races/{gameRace}', ['uses' => 'Api\RacesController@update']);
    Route::get('/admin/races/{gameRace}', ['uses' => 'Api\RacesController@show']);
});
