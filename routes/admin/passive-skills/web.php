<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/passive-skills', ['as' => 'admin.passive-skills.index', 'uses' => 'PassiveSkillsController@index']);
    Route::get('/admin/passive-skills/export', ['as' => 'admin.passive-skills.export', 'uses' => 'PassiveSkillExportController']);
});
