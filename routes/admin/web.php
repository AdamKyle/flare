<?php

use App\Admin\Controllers\LocationsController;

Route::middleware(['auth', 'is.admin'])->group(function() {
    Route::get('/admin', ['as' => 'home', 'uses' => 'AdminController@home']);
    Route::get('/admin/maps', ['as' => 'maps', 'uses' => 'MapsController@index']);
    Route::get('/admin/maps/upload', ['as' => 'maps.upload', 'uses' => 'MapsController@uploadMap']);
    Route::post('/admin/maps/process-upload', ['as' => 'upload.map', 'uses' => 'MapsController@upload']);

    Route::get('/admin/locations', ['as' => 'locations.list', 'uses' => 'LocationsController@index']);
    Route::get('/admin/locations/create', ['as' => 'locations.create', 'uses' => 'LocationsController@create']);
    Route::get('/admin/location/{location}', ['as' => 'locations.location', 'uses' => 'LocationsController@show']);
    Route::get('/admin/locations/{location}/edit', ['as' => 'location.edit', 'uses' => 'LocationsController@edit']);

    Route::get('/admin/adventures', ['as' => 'adventures.list', 'uses' => 'AdventuresController@index']);
    Route::get('/admin/adventures/create', ['as' => 'adventures.create', 'uses' => 'AdventuresController@create']);
    Route::get('/admin/adventures/{adventure}', ['as' => 'adventures.adventure', 'uses' => 'AdventuresController@show']);
    Route::get('/admin/adventures/{adventure}/edit', ['as' => 'adventure.edit', 'uses' => 'AdventuresController@edit']);
    Route::post('/admin/adventures/store', ['as' => 'adventures.store', 'uses' => 'AdventuresController@store']);
    Route::post('/admin/adventures/{adventure}/update', ['as' => 'adventure.update', 'uses' => 'AdventuresController@update']);

    Route::get('/admin/monsters', ['as' => 'monsters.list', 'uses' => 'MonstersController@index']);
    Route::get('/admin/monsters/create', ['as' => 'monsters.create', 'uses' => 'MonstersController@create']);
    Route::get('/admin/monsters/{monster}', ['as' => 'monsters.monster', 'uses' => 'MonstersController@show']);
    Route::get('/admin/monsters/{monster}/edit', ['as' => 'monster.edit', 'uses' => 'MonstersController@edit']);

    Route::get('/admin/items', ['as' => 'items.list', 'uses' => 'ItemsController@index']);
    Route::get('/admin/items/create', ['as' => 'items.create', 'uses' => 'ItemsController@create']);
    Route::get('/admin/items/{item}', ['as' => 'items.item', 'uses' => 'ItemsController@show']);
    Route::get('/admin/items/{item}/edit', ['as' => 'items.edit', 'uses' => 'ItemsController@edit']);
    Route::delete('/admin/items/{item}/delete', ['as' => 'items.delete', 'uses' => 'ItemsController@delete']);
});
