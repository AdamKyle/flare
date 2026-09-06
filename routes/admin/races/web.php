<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/races', ['as' => 'admin.races.index', 'uses' => 'RacesController@index']);
    Route::get('/admin/races/export', ['as' => 'admin.races.export', 'uses' => 'RaceExportController']);
});
