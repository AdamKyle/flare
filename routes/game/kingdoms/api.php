<?php

Route::get('/kingdoms/location', ['as' => 'kingdoms.settle', 'uses' => 'Api\KingdomsController@getLocationData']);
Route::post('/kingdoms/{character}/settle', ['as' => 'kingdoms.settle', 'uses' => 'Api\KingdomsController@settle']);