<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/gem-abilities', ['as' => 'admin.gem-abilities.index', 'uses' => 'GemAbilitiesController@index']);
});
