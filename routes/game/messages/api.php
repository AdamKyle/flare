<?php

Route::get('/server-message', ['uses' => 'Api\MessageController@generateServerMessage']);
Route::get('/user-chat-info/{user}', ['uses' => 'Api\MessageController@fetchUserInfo']);

Route::group(['middleware' => 'throttle:50,2'], function () {
    Route::post('/public-message', ['uses' => 'Api\MessageController@postPublicMessage']);
    Route::post('/private-message', ['uses' => 'Api\MessageController@sendPrivateMessage']);
});    


