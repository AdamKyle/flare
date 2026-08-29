<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/npcs', ['as' => 'admin.npcs.index', 'uses' => 'NpcsController@index']);
    Route::get('/admin/npcs/export', ['as' => 'admin.npcs.export', 'uses' => 'NpcExportController']);
    Route::redirect('/admin/npcs/index', '/admin/npcs')->name('npcs.index');
    Route::get('/admin/npcs/create', ['as' => 'npcs.create', 'uses' => 'NpcsController@index']);
    Route::get('/admin/npcs/edit/{npc}', ['as' => 'npcs.edit', 'uses' => 'NpcsController@edit']);
    Route::get('/admin/npcs/{npc}', ['as' => 'npcs.show', 'uses' => 'NpcsController@show']);
});
