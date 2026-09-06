<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/class-masteries', ['as' => 'admin.class-masteries.index', 'uses' => 'ClassMasteriesController@index']);
    Route::get('/admin/class-masteries/export', ['as' => 'admin.class-masteries.export', 'uses' => 'ClassMasteryExportController']);
});
