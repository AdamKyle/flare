<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/game-maps', ['as' => 'admin.game-maps.index', 'uses' => 'GameMapsController@index']);
    Route::get('/admin/game-maps/export', ['as' => 'admin.game-maps.export', 'uses' => 'GameMapExportController']);
});
