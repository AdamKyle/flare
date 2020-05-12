<?php


Route::middleware(['auth', 'is.admin'])->group(function() {
    Route::get('/admin/maps', ['as' => 'maps', 'uses' => 'MapsController@index']);
    Route::get('/admin/maps/upload', ['as' => 'maps.upload', 'uses' => 'MapsController@uploadMap']);
    Route::post('/admin/maps/process-upload', ['as' => 'upload.map', 'uses' => 'MapsController@upload']);
});
