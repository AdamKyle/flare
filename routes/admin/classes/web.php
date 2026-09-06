<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/classes', ['as' => 'admin.classes.index', 'uses' => 'ClassesController@index']);
    Route::get('/admin/classes/export', ['as' => 'admin.classes.export', 'uses' => 'ClassExportController']);
});
