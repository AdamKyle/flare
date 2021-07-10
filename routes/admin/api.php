<?php

Route::middleware(['auth', 'is.admin'])->group(function() {
    Route::get('/admin/chat-messages', ['uses' => 'Api\AdminMessagesController@index']);

    Route::post('/admin/ban-user', ['uses' => 'Api\AdminMessagesController@banUser']);
    Route::post('/admin/silence-user', ['uses' => 'Api\AdminMessagesController@silenceUser']);
    Route::post('/admin/force-name-change/{user}', ['uses' => 'Api\AdminMessagesController@forceNameChange']);

    Route::get('/admin/site-statistics', ['uses' => 'Api\SiteAccessStatisticsController@index']);
    Route::get('/admin/site-statistics/all-time-sign-in', ['uses' => 'Api\SiteAccessStatisticsController@fetchLoggedInAllTime']);
    Route::get('/admin/site-statistics/all-time-register', ['uses' => 'Api\SiteAccessStatisticsController@fetchRegisteredAllTime']);
    Route::get('/admin/site-statistics/all-characters-gold', ['uses' => 'Api\SiteAccessStatisticsController@fetchCharactersGold']);
});
