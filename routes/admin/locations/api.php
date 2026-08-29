<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/locations', ['uses' => 'Api\LocationsController@index']);
    Route::post('/admin/locations/import', ['uses' => 'Api\LocationImportController']);
    Route::get('/admin/locations/{location}/quest-items', ['uses' => 'Api\LocationsController@questItems']);
    Route::get('/admin/locations/{location}', ['uses' => 'Api\LocationsController@showLocation']);

    Route::get('/admin/game-maps/{gameMap}/locations/options', ['uses' => 'Api\LocationsController@options']);
    Route::get('/admin/game-maps/{gameMap}/locations/{location}', ['uses' => 'Api\LocationsController@show']);
    Route::post('/admin/game-maps/{gameMap}/locations', ['uses' => 'Api\LocationsController@store']);
    Route::put('/admin/game-maps/{gameMap}/locations/{location}', ['uses' => 'Api\LocationsController@update']);
    Route::patch('/admin/game-maps/{gameMap}/locations/{location}/position', ['uses' => 'Api\LocationsController@move']);
});
