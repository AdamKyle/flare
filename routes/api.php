<?php

Route::middleware('throttle:20,1')->group(function () {
    Route::get('/items-list', ['uses' => 'Api\ItemsController@fetchCraftableItems']);
    Route::get('/items-list-for-type', ['uses' => 'Api\ItemsController@fetchSpecificSet']);

    Route::get('/characters-online', ['uses' => 'Api\OnlineUsersController@getCharactersOnline']);
    Route::get('/user-login-duration', ['uses' => 'Api\OnlineUsersController@getLoginDurationDetails']);
    Route::get('/character-logins', ['uses' => 'Api\OnlineUsersController@getLoginStats']);
    Route::get('/character-registrations', ['uses' => 'Api\OnlineUsersController@getRegistrationStats']);
});
