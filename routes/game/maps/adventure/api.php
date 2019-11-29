<?php

Route::get('/map/{user}', ['uses' => 'Api\MapController@index']);
Route::post('/move/{character}', ['uses' => 'Api\MapController@move']);
