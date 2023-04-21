<?php

Route::group(['middleware' => 'auth'], function() {
    Route::get('/server-message', ['uses' => 'Api\ServerMessageController@generateServerMessage']);
    Route::get('/last-chats', ['uses' => 'Api\FetchMessagesController@fetchChatMessages']);

    Route::group(['middleware' => 'throttle:chat'], function () {
        Route::post('/public-message', ['uses' => 'Api\PostMessagesController@postPublicMessage']);
        Route::post('/private-message', ['uses' => 'Api\PostMessagesController@sendPrivateMessage']);

        Route::middleware(['is.character.exploring'])->group(function() {
            Route::post('/public-entity/', ['uses' => 'Api\CommandsController@publicEntity']);
        });
    });
});



