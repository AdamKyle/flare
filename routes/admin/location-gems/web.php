<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/location-gems', ['as' => 'admin.location-gems.index', 'uses' => 'LocationGemsController@index']);
    Route::get('/admin/location-gems/export', ['as' => 'admin.location-gems.export', 'uses' => 'LocationGemExportController']);
});
