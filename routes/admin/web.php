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
    Route::post('/admin/items/{item}/delete', ['as' => 'items.delete', 'uses' => 'ItemsController@delete']);

    Route::get('/admin/affixes', ['as' => 'affixes.list', 'uses' => 'AffixesController@index']);
    Route::get('/admin/affixes/create', ['as' => 'affixes.create', 'uses' => 'AffixesController@create']);
    Route::get('/admin/affixes/{affix}', ['as' => 'affixes.affix', 'uses' => 'AffixesController@show']);
    Route::get('/admin/affixes/{affix}/edit', ['as' => 'affixes.edit', 'uses' => 'AffixesController@edit']);
    Route::post('/admin/affixes/{affix}/delete', ['as' => 'affixes.delete', 'uses' => 'AffixesController@delete']);

    Route::get('/admin/users', ['as' => 'users.list', 'uses' => 'UsersController@index']);
    Route::get('/admin/user/{user}', ['as' => 'users.user', 'uses' => 'UsersController@show']);
    Route::post('/admin/user/{user}/reset-password', ['as' => 'user.reset.password', 'uses' => 'UsersController@resetPassword']);
    Route::post('/admin/user/{user}/silence-user', ['as' => 'user.silence', 'uses' => 'UsersController@silenceUser']);
    Route::post('/admin/users/{user}/ban-user', ['as' => 'ban.user', 'uses' => 'UsersController@banUser']);
    Route::post('/admin/users/{user}/un-ban-user', ['as' => 'unban.user', 'uses' => 'UsersController@unBanUser']);

    Route::get('/admin/skills', ['as' => 'skills.list', 'uses' => 'SkillsController@index']);
    Route::get('/admin/skill/{skill}', ['as' => 'skills.skill', 'uses' => 'SkillsController@show']);
    Route::get('/admin/skills/create', ['as' => 'skills.create', 'uses' => 'SkillsController@create']);
    Route::get('/admin/skill/{skill}/edit', ['as' => 'skill.edit', 'uses' => 'SkillsController@edit']);

    Route::get('/admin/races', ['as' => 'races.list', 'uses' => 'RacesController@index']);
    Route::get('/admin/races/create', ['as' => 'races.create', 'uses' => 'RacesController@create']);
    Route::get('/admin/races/{race}', ['as' => 'races.race', 'uses' => 'RacesController@show']);
    Route::get('/admin/races/{race}/edit', ['as' => 'races.edit', 'uses' => 'RacesController@edit']);

    Route::get('/admin/classes', ['as' => 'classes.list', 'uses' => 'ClassesController@index']);
    Route::get('/admin/classes/create', ['as' => 'classes.create', 'uses' => 'ClassesController@create']);
    Route::get('/admin/classes/{class}', ['as' => 'classes.class', 'uses' => 'ClassesController@show']);
    Route::get('/admin/classes/{class}/edit', ['as' => 'classes.edit', 'uses' => 'ClassesController@edit']);
    
});
