<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/skills', ['as' => 'admin.skills.index', 'uses' => 'SkillsController@index']);
    Route::get('/admin/skills/export', ['as' => 'admin.skills.export', 'uses' => 'SkillExportController']);
});
