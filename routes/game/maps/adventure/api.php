<?php

Route::get('/map/{user}', ['uses' => 'Api\MapController@index']);
Route::get('/is-water/{character}', ['uses' => 'Api\MapController@iswater']);
Route::post('/move/{character}', ['uses' => 'Api\MapController@move']);
