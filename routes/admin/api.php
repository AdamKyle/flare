<?php

Route::middleware(['auth:api', 'is.admin'])->group(function() {
    Route::get('/admin/chat-messages', ['uses' => 'Api\Messagescontroller@index']);
    Route::post('/admin/ban-user', ['uses' => 'Api\MessagesController@banUser']);
    Route::post('/admin/silence-user', ['uses' => 'Api\MessagesController@silenceUser']);
    Route::post('/admin/force-name-change/{user}', ['uses' => 'Api\MessagesController@forceNameChange']);
});
