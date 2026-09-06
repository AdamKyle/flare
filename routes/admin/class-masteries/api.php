<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/class-masteries', ['uses' => 'Api\ClassMasteriesController@index']);
    Route::get('/admin/class-masteries/options', ['uses' => 'Api\ClassMasteriesController@options']);
    Route::post('/admin/class-masteries', ['uses' => 'Api\ClassMasteriesController@store']);
    Route::post('/admin/class-masteries/import', ['uses' => 'Api\ClassMasteryImportController']);
    Route::get('/admin/class-masteries/{gameClassSpecial}/edit', ['uses' => 'Api\ClassMasteriesController@edit']);
    Route::put('/admin/class-masteries/{gameClassSpecial}', ['uses' => 'Api\ClassMasteriesController@update']);
    Route::get('/admin/class-masteries/{gameClassSpecial}', ['uses' => 'Api\ClassMasteriesController@show']);
});
