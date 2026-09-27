<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/kingdoms/buildings', ['uses' => 'Api\BuildingsController@index']);
    Route::get('/admin/kingdoms/buildings/options', ['uses' => 'Api\BuildingsController@options']);
    Route::post('/admin/kingdoms/buildings', ['uses' => 'Api\BuildingsController@store']);
    Route::get('/admin/kingdoms/buildings/{gameBuilding}/edit', ['uses' => 'Api\BuildingsController@edit']);
    Route::put('/admin/kingdoms/buildings/{gameBuilding}', ['uses' => 'Api\BuildingsController@update']);
    Route::get('/admin/kingdoms/buildings/{gameBuilding}', ['uses' => 'Api\BuildingsController@show']);

    Route::get('/admin/kingdoms/units', ['uses' => 'Api\UnitsController@index']);
    Route::post('/admin/kingdoms/units', ['uses' => 'Api\UnitsController@store']);
    Route::get('/admin/kingdoms/units/{gameUnit}/edit', ['uses' => 'Api\UnitsController@edit']);
    Route::put('/admin/kingdoms/units/{gameUnit}', ['uses' => 'Api\UnitsController@update']);
    Route::get('/admin/kingdoms/units/{gameUnit}', ['uses' => 'Api\UnitsController@show']);

    Route::post('/admin/kingdoms/import', ['uses' => 'Api\KingdomImportController']);
});
