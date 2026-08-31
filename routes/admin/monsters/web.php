<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/monsters', ['as' => 'admin.monsters.index', 'uses' => 'MonstersController@index']);
    Route::get('/admin/monsters/export', ['as' => 'admin.monsters.export', 'uses' => 'MonsterExportController']);

    // Legacy Monster Admin route-name compatibility: the old Blade create/edit pages are
    // retired; anything still generating these URLs lands on the modern Monsters application.
    Route::redirect('/admin/monsters/list-legacy', '/admin/monsters')->name('monsters.list');
    Route::redirect('/admin/monsters/create-legacy', '/admin/monsters')->name('monsters.create');
    Route::redirect('/admin/monsters/edit-legacy/{monster}', '/admin/monsters')->name('monster.edit');
});
