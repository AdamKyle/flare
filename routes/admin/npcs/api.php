<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/game-maps/{gameMap}/npcs/options', ['uses' => 'Api\NpcsController@options']);
    Route::get('/admin/game-maps/{gameMap}/npcs/{npc}', ['uses' => 'Api\NpcsController@show']);
    Route::post('/admin/game-maps/{gameMap}/npcs', ['uses' => 'Api\NpcsController@store']);
    Route::put('/admin/game-maps/{gameMap}/npcs/{npc}', ['uses' => 'Api\NpcsController@update']);
    Route::patch('/admin/game-maps/{gameMap}/npcs/{npc}/position', ['uses' => 'Api\NpcsController@move']);
});
