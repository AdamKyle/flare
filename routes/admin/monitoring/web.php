<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/character-reward-queue', [
        'as' => 'admin.character-reward-queue',
        'uses' => 'BattleRewardQueueController@index',
    ]);

    Route::get('/admin/monitoring/exploration', ['as' => 'admin.monitoring.exploration', 'uses' => 'MonitoringController@exploration']);
    Route::get('/admin/monitoring/faction-loyalty', ['as' => 'admin.monitoring.faction-loyalty', 'uses' => 'MonitoringController@factionLoyalty']);
    Route::get('/admin/monitoring/delve', ['as' => 'admin.monitoring.delve', 'uses' => 'MonitoringController@delve']);
    Route::get('/admin/monitoring/batch-crafting', ['as' => 'admin.monitoring.batch-crafting', 'uses' => 'MonitoringController@batchCrafting']);
    Route::get('/admin/monitoring/logs', ['as' => 'admin.monitoring.logs', 'uses' => 'MonitoringController@logs']);
});
