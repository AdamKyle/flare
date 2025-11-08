<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/chat-messages', ['uses' => 'Api\AdminMessagesController@index']);

    Route::get('/admin/site-statistics/all-time-sign-in', ['uses' => 'Api\SiteAccessStatisticsController@fetchLoggedInAllTime']);
    Route::get('/admin/site-statistics/all-time-register', ['uses' => 'Api\SiteAccessStatisticsController@fetchRegisteredAllTime']);
    Route::get('/admin/site-statistics/quest-completion', ['uses' => 'Api\SiteAccessStatisticsController@fetchCompletedQuests']);
    Route::get('/admin/site-statistics/other-stats', ['uses' => 'Api\SiteAccessStatisticsController@otherDetails']);
    Route::get('/admin/site-statistics/reincarnation', ['uses' => 'Api\SiteAccessStatisticsController@fetchReincarnationChart']);
    Route::get('/admin/site-statistics/character-total-gold', ['uses' => 'Api\SiteAccessStatisticsController@getTotalGoldIncludingKingdomsForCharacters']);
    Route::get('/admin/site-statistics/login-duration', ['uses' => 'Api\SiteAccessStatisticsController@getLoginDurationDetails']);
    Route::get('/admin/site-statistics/characters-online', ['uses' => 'Api\SiteAccessStatisticsController@getUsersCurrentlyOnline']);

    Route::get('/admin/info-section/page', ['uses' => 'Api\InformationController@getPage']);
    Route::post('/admin/info-section/store-page', ['uses' => 'Api\InformationController@storePage']);
    Route::post('/admin/info-section/update-page', ['uses' => 'Api\InformationController@updatePage']);
    Route::post('/admin/info-section/add-section', ['uses' => 'Api\InformationController@addSection']);
    Route::post('/admin/info-section/delete-section/{infoPage}', ['uses' => 'Api\InformationController@deleteSection']);
    Route::post('/admin/info-section/delete-page', ['uses' => 'Api\InformationController@deletePage']);

    Route::get('/admin/event-calendar/fetch-events', ['uses' => 'Api\EventScheduleController@index']);
    Route::post('/admin/create-new-event', ['uses' => 'Api\EventScheduleController@createEvent']);
    Route::post('/admin/update-event/{scheduledEvent}', ['uses' => 'Api\EventScheduleController@updateEvent']);
    Route::post('/admin/delete-event', ['uses' => 'Api\EventScheduleController@deleteEvent']);
    Route::post('/admin/create-multiple-events', ['uses' => 'Api\EventScheduleController@createMultipleEvents']);

    route::get('/admin/map-manager/{gameMap}', ['uses' => 'Api\MapManagerController@getMapData']);
    route::post('/admin/map-manager/move/{gameMap}', ['uses' => 'Api\MapManagerController@moveLocation']);

    route::get('/admin/fetch-survey/{survey}', ['uses' => 'Api\SurveyController@fetchSurvey']);
    route::post('/admin/create-new-survey', ['uses' => 'Api\SurveyController@createSurvey']);
    route::post('/admin/save-survey/{survey}', ['uses' => 'Api\SurveyController@saveSurvey']);

    route::get('/admin/manage-guide-quest', ['uses' => 'Api\GuideQuests\GuideQuestsController@guideQuest']);
});
