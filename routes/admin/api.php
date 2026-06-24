<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/chat-messages', ['uses' => 'Api\AdminMessagesController@index']);
    Route::get('/admin/character-reward-queue/summary', ['uses' => 'Api\BattleRewardQueueController@summary']);
    Route::get('/admin/character-reward-queue/charts', ['uses' => 'Api\BattleRewardQueueController@charts']);
    Route::get('/admin/character-reward-queue/characters', ['uses' => 'Api\BattleRewardQueueController@characters']);
    Route::get('/admin/character-reward-queue/characters/{characterId}', ['uses' => 'Api\BattleRewardQueueController@characterDetail']);
    Route::get('/admin/character-reward-queue/requests', ['uses' => 'Api\BattleRewardQueueController@requests']);
    Route::get('/admin/character-reward-queue/status-breakdown', ['uses' => 'Api\BattleRewardQueueController@statusBreakdown']);
    Route::get('/admin/character-reward-queue/stale', ['uses' => 'Api\BattleRewardQueueController@stale']);
    Route::post('/admin/character-reward-queue/stale/repair', ['uses' => 'Api\BattleRewardQueueController@repairStale']);

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

    route::get('admin/map-manager/{gameMap}', ['uses' => 'Api\MapManagerController@getMapData']);
    route::post('admin/map-manager/move/{gameMap}', ['uses' => 'Api\MapManagerController@moveLocation']);

    Route::get('/admin/monitoring/exploration/active', ['uses' => 'Api\MonitoringController@explorationActive']);
    Route::get('/admin/monitoring/exploration/logs', ['uses' => 'Api\MonitoringController@explorationLogs']);
    Route::get('/admin/monitoring/exploration/summary', ['uses' => 'Api\MonitoringController@explorationSummary']);
    Route::get('/admin/monitoring/exploration/chart', ['uses' => 'Api\MonitoringController@explorationChart']);

    Route::get('/admin/monitoring/faction-loyalty/active', ['uses' => 'Api\MonitoringController@factionLoyaltyActive']);
    Route::get('/admin/monitoring/faction-loyalty/runs', ['uses' => 'Api\MonitoringController@factionLoyaltyRuns']);
    Route::get('/admin/monitoring/faction-loyalty/summary', ['uses' => 'Api\MonitoringController@factionLoyaltySummary']);
    Route::get('/admin/monitoring/faction-loyalty/chart', ['uses' => 'Api\MonitoringController@factionLoyaltyChart']);

    Route::get('/admin/monitoring/delve/active', ['uses' => 'Api\MonitoringController@delveActive']);
    Route::get('/admin/monitoring/delve/runs', ['uses' => 'Api\MonitoringController@delveRuns']);
    Route::get('/admin/monitoring/delve/summary', ['uses' => 'Api\MonitoringController@delveSummary']);
    Route::get('/admin/monitoring/delve/chart', ['uses' => 'Api\MonitoringController@delveChart']);

    Route::get('/admin/monitoring/logs/files', ['uses' => 'Api\AdminLogsDashboardController@files']);
    Route::get('/admin/monitoring/logs/entries', ['uses' => 'Api\AdminLogsDashboardController@entries']);
    Route::get('/admin/monitoring/logs/summary', ['uses' => 'Api\AdminLogsDashboardController@summary']);

});
