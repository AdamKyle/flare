<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/character-reward-queue/summary', ['uses' => 'Api\BattleRewardQueueController@summary']);
    Route::get('/admin/character-reward-queue/charts', ['uses' => 'Api\BattleRewardQueueController@charts']);
    Route::get('/admin/character-reward-queue/characters', ['uses' => 'Api\BattleRewardQueueController@characters']);
    Route::get('/admin/character-reward-queue/characters/{characterId}', ['uses' => 'Api\BattleRewardQueueController@characterDetail']);
    Route::get('/admin/character-reward-queue/requests', ['uses' => 'Api\BattleRewardQueueController@requests']);
    Route::get('/admin/character-reward-queue/status-breakdown', ['uses' => 'Api\BattleRewardQueueController@statusBreakdown']);
    Route::get('/admin/character-reward-queue/stale', ['uses' => 'Api\BattleRewardQueueController@stale']);
    Route::post('/admin/character-reward-queue/stale/repair', ['uses' => 'Api\BattleRewardQueueController@repairStale']);

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

    Route::get('/admin/monitoring/batch-crafting/active', ['uses' => 'Api\MonitoringController@batchCraftingActive']);
    Route::get('/admin/monitoring/batch-crafting/runs', ['uses' => 'Api\MonitoringController@batchCraftingRuns']);
    Route::get('/admin/monitoring/batch-crafting/summary', ['uses' => 'Api\MonitoringController@batchCraftingSummary']);
    Route::get('/admin/monitoring/batch-crafting/chart', ['uses' => 'Api\MonitoringController@batchCraftingChart']);

    Route::get('/admin/monitoring/logs/files', ['uses' => 'Api\AdminLogsDashboardController@files']);
    Route::get('/admin/monitoring/logs/entries', ['uses' => 'Api\AdminLogsDashboardController@entries']);
    Route::get('/admin/monitoring/logs/entry-detail', ['uses' => 'Api\AdminLogsDashboardController@entryDetail']);
    Route::get('/admin/monitoring/logs/poll', ['uses' => 'Api\AdminLogsDashboardController@poll']);
    Route::get('/admin/monitoring/logs/bugs', ['uses' => 'Api\AdminLogsDashboardController@bugs']);
    Route::get('/admin/monitoring/logs/bug-chart', ['uses' => 'Api\AdminLogsDashboardController@bugChart']);
});
