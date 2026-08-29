<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/npcs', ['uses' => 'Api\NpcsController@index']);
    Route::post('/admin/npcs/import', ['uses' => 'Api\NpcImportController']);
    Route::get('/admin/npcs/{npc}/quests', ['uses' => 'Api\NpcsController@quests']);
    Route::get('/admin/npcs/{npc}/reward-items', ['uses' => 'Api\NpcsController@rewardItems']);
    Route::get('/admin/npcs/{npc}', ['uses' => 'Api\NpcsController@showNpc']);

    Route::get('/admin/game-maps/{gameMap}/npcs/options', ['uses' => 'Api\NpcsController@options']);
    Route::get('/admin/game-maps/{gameMap}/npcs/{npc}', ['uses' => 'Api\NpcsController@show']);
    Route::post('/admin/game-maps/{gameMap}/npcs', ['uses' => 'Api\NpcsController@store']);
    Route::put('/admin/game-maps/{gameMap}/npcs/{npc}', ['uses' => 'Api\NpcsController@update']);
    Route::patch('/admin/game-maps/{gameMap}/npcs/{npc}/position', ['uses' => 'Api\NpcsController@move']);
});
