<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/map-gems', ['as' => 'admin.map-gems.index', 'uses' => 'MapGemsController@index']);
    Route::get('/admin/map-gems/export', ['as' => 'admin.map-gems.export', 'uses' => 'MapGemExportController']);
});
