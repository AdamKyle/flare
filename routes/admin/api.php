<?php

Route::middleware(['auth', 'is.admin'])->group(function() {
    Route::get('/admin/chat-messages', ['uses' => 'Api\AdminMessagesController@index']);

    Route::get('/admin/site-statistics/all-time-sign-in', ['uses' => 'Api\SiteAccessStatisticsController@fetchLoggedInAllTime']);
    Route::get('/admin/site-statistics/all-time-register', ['uses' => 'Api\SiteAccessStatisticsController@fetchRegisteredAllTime']);
    Route::get('/admin/site-statistics/all-characters-gold', ['uses' => 'Api\SiteAccessStatisticsController@fetchCharactersGold']);
    Route::get('/admin/site-statistics/other-stats', ['uses' => 'Api\SiteAccessStatisticsController@otherDetails']);

    Route::get('/admin/info-section/page', ['uses' => 'Api\InformationController@getPage']);
    Route::post('/admin/info-section/store-page', ['uses' => 'Api\InformationController@storePage']);
    Route::post('/admin/info-section/update-page', ['uses' => 'Api\InformationController@updatePage']);
    Route::post('/admin/info-section/delete-section/{infoPage}', ['uses' => 'Api\InformationController@deleteSection']);
    Route::post('/admin/info-section/delete-page', ['uses' => 'Api\InformationController@deletePage']);
});
