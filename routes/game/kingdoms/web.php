<?php

Route::get('/kingdom/{character}/attack-logs', ['as' => 'game.kingdom.attack-logs', 'uses' => 'KingdomsController@attackLogs']);
Route::post('/kingdom/{character}/attack-logs/batch-delete', ['as' => 'game.kingdom.batch-delete-logs', 'uses' => 'KingdomsController@batchDeleteLogs']);
Route::post('/kingdom/{character}/attack-logs/delete/{kingdomLog}', ['as' => 'game.kingdom.delete-log', 'uses' => 'KingdomsController@deleteLog']);
