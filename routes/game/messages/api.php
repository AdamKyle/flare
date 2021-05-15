<?php

Route::middleware(['auth:api'])->group(function() {
    Route::get('/server-message', ['uses' => 'Api\MessageController@generateServerMessage']);
    Route::get('/user-chat-info/{user}', ['uses' => 'Api\MessageController@fetchUserInfo']);
    Route::get('/last-chats', ['uses' => 'Api\MessageController@fetchMessages']);

    Route::group(['middleware' => 'throttle:chat'], function () {
        Route::post('/public-message', ['uses' => 'Api\MessageController@postPublicMessage']);
        Route::post('/private-message', ['uses' => 'Api\MessageController@sendPrivateMessage']);
    });
});



