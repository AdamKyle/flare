<?php

Route::get('/server-message', ['uses' => 'Api\MessageController@generateServerMessage']);
Route::post('/public-message', ['uses' => 'Api\MessageController@postPublicMessage']);
Route::post('/private-message', ['uses' => 'Api\MessageController@sendPrivateMessage']);
