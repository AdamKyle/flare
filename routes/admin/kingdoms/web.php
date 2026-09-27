<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/kingdoms/buildings', ['as' => 'admin.kingdoms.buildings.index', 'uses' => 'BuildingsController@index']);
    Route::get('/admin/kingdoms/units', ['as' => 'admin.kingdoms.units.index', 'uses' => 'UnitsController@index']);
    Route::get('/admin/kingdoms/export', ['as' => 'admin.kingdoms.export', 'uses' => 'KingdomExportController']);
});
