<?php

Route::middleware(['auth', 'is.admin'])->group(function() {
    Route::get('/admin/chat-messages', ['uses' => 'Api\AdminMessagesController@index']);

    Route::get('/admin/site-statistics', ['uses' => 'Api\SiteAccessStatisticsController@index']);
    Route::get('/admin/site-statistics/all-time-sign-in', ['uses' => 'Api\SiteAccessStatisticsController@fetchLoggedInAllTime']);
    Route::get('/admin/site-statistics/all-time-register', ['uses' => 'Api\SiteAccessStatisticsController@fetchRegisteredAllTime']);
    Route::get('/admin/site-statistics/all-characters-gold', ['uses' => 'Api\SiteAccessStatisticsController@fetchCharactersGold']);

    Route::post('/admin/info-section/store-page', ['uses' => 'Api\InformationController@storePage']);
});
