<?php

use App\Admin\Controllers\LocationsController;

Route::middleware(['auth', 'is.admin'])->group(function() {
    Route::get('/admin/maps', ['as' => 'maps', 'uses' => 'MapsController@index']);
    Route::get('/admin/maps/upload', ['as' => 'maps.upload', 'uses' => 'MapsController@uploadMap']);
    Route::post('/admin/maps/process-upload', ['as' => 'upload.map', 'uses' => 'MapsController@upload']);

    Route::get('/admin/locations', ['as' => 'locations.list', 'uses' => 'LocationsController@index']);
    Route::get('/admin/locations/create', ['as' => 'locations.create', 'uses' => 'LocationsController@create']);
    Route::get('/admin/location/{location}', ['as' => 'locations.location', 'uses' => 'LocationsController@show']);
    Route::get('/admin/locations/{location}/edit', ['as' => 'location.edit', 'uses' => 'LocationsController@edit']);
    Route::post('/admin/locations/store', ['as' => 'locations.store', 'uses' => 'LocationsController@store']);
    Route::post('/admin/locations/{location}/update', ['as' => 'location.update', 'uses' => 'LocationsController@update']);

    Route::get('/admin/adventures', ['as' => 'adventures.list', 'uses' => 'AdventuresController@index']);
    Route::get('/admin/adventures/create', ['as' => 'adventures.create', 'uses' => 'AdventuresController@create']);
    Route::get('/admin/adventures/{adventure}', ['as' => 'adventures.adventure', 'uses' => 'AdventuresController@show']);
    Route::get('/admin/adventures/{adventure}/edit', ['as' => 'adventure.edit', 'uses' => 'AdventuresController@edit']);
    Route::post('/admin/adventures/store', ['as' => 'adventures.store', 'uses' => 'AdventuresController@store']);
    Route::post('/admin/adventures/{adventure}/update', ['as' => 'adventure.update', 'uses' => 'AdventuresController@update']);

    Route::get('/admin/monsters', ['as' => 'monsters.list', 'uses' => 'MonstersController@index']);
    Route::get('/admin/monsters/create', ['as' => 'monsters.create', 'uses' => 'MonstersController@create']);
    Route::get('/admin/monsters/{monster}', ['as' => 'monsters.adventure', 'uses' => 'MonstersController@show']);
    Route::get('/admin/monsters/{monster}/edit', ['as' => 'monsters.edit', 'uses' => 'MonstersController@edit']);
    Route::post('/admin/monsters/store', ['as' => 'monsters.store', 'uses' => 'MonstersController@store']);
    Route::post('/admin/monsters/{monster}/update', ['as' => 'monsters.update', 'uses' => 'MonstersController@update']);
});
