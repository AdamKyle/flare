<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/items', ['as' => 'admin.items.index', 'uses' => 'ItemsController@index']);
    Route::get('/admin/items/export', ['as' => 'admin.items.export', 'uses' => 'ItemExportController']);
    Route::redirect('/admin/items/list', '/admin/items')->name('items.list');
    Route::get('/admin/items/edit/{item}', ['as' => 'items.edit', 'uses' => 'ItemsController@edit']);
    Route::match(['get', 'post'], '/admin/items/import-data', function () {
        return redirect()->route('admin.items.index');
    })->name('items.import-data');
});
