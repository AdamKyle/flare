<?php

Route::get('/kingdoms/location', ['as' => 'kingdoms.settle', 'uses' => 'Api\KingdomsController@getLocationData']);
Route::post('/kingdoms/{character}/settle', ['as' => 'kingdoms.settle', 'uses' => 'Api\KingdomsController@settle']);
Route::post('/kingdoms/{character}/upgrade-building/{building}', ['as' => 'kingdoms.building.upgrade', 'uses' => 'Api\KingdomsController@upgradeBuilding']);