<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/locations', ['as' => 'admin.locations.index', 'uses' => 'LocationsController@index']);
    Route::get('/admin/locations/export', ['as' => 'admin.locations.export', 'uses' => 'LocationExportController']);
    Route::get('/admin/location/{location}', ['as' => 'locations.location', 'uses' => 'LocationsController@show']);
    Route::get('/admin/locations/{location}/edit', ['as' => 'location.edit', 'uses' => 'LocationsController@edit']);
});
