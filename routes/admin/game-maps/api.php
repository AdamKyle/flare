<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/game-maps', ['uses' => 'Api\GameMapsController@index']);
    Route::get('/admin/game-maps/options', ['uses' => 'Api\GameMapsController@options']);
    Route::post('/admin/game-maps', ['uses' => 'Api\GameMapsController@store']);
    Route::post('/admin/game-maps/import', ['uses' => 'Api\GameMapImportController']);
    Route::get('/admin/game-maps/{gameMap}/editor', ['uses' => 'Api\GameMapsController@editor']);
    Route::get('/admin/game-maps/{gameMap}/edit', ['uses' => 'Api\GameMapsController@edit']);
    Route::get('/admin/game-maps/{gameMap}/related-locations', ['uses' => 'Api\GameMapsController@relatedLocations']);
    Route::get('/admin/game-maps/{gameMap}/related-npcs', ['uses' => 'Api\GameMapsController@relatedNpcs']);
    Route::get('/admin/game-maps/{gameMap}/related-monsters', ['uses' => 'Api\GameMapsController@relatedMonsters']);
    Route::get('/admin/game-maps/{gameMap}/related-quests', ['uses' => 'Api\GameMapsController@relatedQuests']);
    Route::get('/admin/game-maps/{gameMap}/related-quest-items', ['uses' => 'Api\GameMapsController@relatedQuestItems']);
    Route::put('/admin/game-maps/{gameMap}', ['uses' => 'Api\GameMapsController@update']);
    Route::get('/admin/game-maps/{gameMap}', ['uses' => 'Api\GameMapsController@show']);
});
