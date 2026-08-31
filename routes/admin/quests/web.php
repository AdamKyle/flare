<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/quests', ['as' => 'admin.quests.index', 'uses' => 'QuestsController@index']);
    Route::get('/admin/quests/export', ['as' => 'admin.quests.export', 'uses' => 'QuestExportController']);

    // Legacy Quest Admin route-name compatibility: the old Blade create/edit/show/index
    // pages are retired; anything still generating these URLs lands on the modern
    // Quests application instead of the legacy controller.
    Route::redirect('/admin/quests/index-legacy', '/admin/quests')->name('quests.index');
    Route::redirect('/admin/quests/create-legacy', '/admin/quests')->name('quests.create');
    Route::redirect('/admin/quests/edit-legacy/{quest}', '/admin/quests')->name('quests.edit');
    Route::redirect('/admin/quests/show-legacy/{quest}', '/admin/quests')->name('quests.show');
});
