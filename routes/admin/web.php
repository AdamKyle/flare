<?php

Route::get('/affixes/{affix}', ['as' => 'game.affixes.affix', 'uses' => 'AffixesController@show']);
Route::get('/game/quests/{quest}', ['as' => 'game.quests.show', 'uses' => 'QuestsController@show']);

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin', ['as' => 'home', 'uses' => 'AdminController@home']);

    Route::redirect('/admin/chat-logs', '/admin')->name('admin.chat-logs');

    Route::redirect('/admin/maps', '/admin')->name('maps');
    Route::get('/admin/maps/upload', ['as' => 'maps.upload', 'uses' => 'MapsController@uploadMap']);
    Route::get('/admin/maps/{gameMap}', ['as' => 'map', 'uses' => 'MapsController@show']);
    Route::get('/admin/maps/{gameMap}/add-bonuses', ['as' => 'map.bonuses', 'uses' => 'MapsController@manageBonuses']);
    Route::get('/admin/manage-map-locations/{gameMap}', ['as' => 'map.manage-locations', 'uses' => 'MapsController@manageMapLocations']);
    Route::post('/admin/maps/process-upload', ['as' => 'upload.map', 'uses' => 'MapsController@upload']);
    Route::post('/admin/maps/{gameMap}/post-bonuses', ['as' => 'add.map.bonuses', 'uses' => 'MapsController@postBonuses']);

    Route::redirect('/admin/location-templates', '/admin')->name('admin.location-templates.list');
    Route::get('/admin/location-templates/create', ['as' => 'admin.location-templates.create', 'uses' => 'LocationTemplatesController@create']);
    Route::get('/admin/location-templates/export-data', ['as' => 'admin.location-templates.export-data', 'uses' => 'LocationTemplatesController@exportLocationTemplates']);
    Route::get('/admin/location-templates/import-data', ['as' => 'admin.location-templates.import-data', 'uses' => 'LocationTemplatesController@importLocationTemplates']);
    Route::get('/admin/location-templates/{locationTemplate}/edit', ['as' => 'admin.location-templates.edit', 'uses' => 'LocationTemplatesController@edit']);
    Route::get('/admin/location-templates/{locationTemplate}', ['as' => 'admin.location-templates.show', 'uses' => 'LocationTemplatesController@show']);
    Route::post('/admin/location-templates/store', ['as' => 'admin.location-templates.store', 'uses' => 'LocationTemplatesController@store']);
    Route::post('/admin/location-templates/{locationTemplate}/delete', ['as' => 'admin.location-templates.delete', 'uses' => 'LocationTemplatesController@delete']);
    Route::post('/admin/location-templates/export', ['as' => 'admin.location-templates.export', 'uses' => 'LocationTemplatesController@export']);
    Route::post('/admin/location-templates/import', ['as' => 'admin.location-templates.import', 'uses' => 'LocationTemplatesController@importData']);

    Route::get('/admin/affixes/export-affixes', ['as' => 'affixes.export', 'uses' => 'AffixesController@exportItems']);
    Route::get('/admin/affixes/import-affixes', ['as' => 'affixes.import', 'uses' => 'AffixesController@importItems']);
    Route::post('/admin/affixes/export-data', ['as' => 'affixes.export-data', 'uses' => 'AffixesController@export']);
    Route::post('/admin/affixes/import-data', ['as' => 'affixes.import-data', 'uses' => 'AffixesController@importData']);

    Route::redirect('/admin/affixes', '/admin')->name('affixes.list');
    Route::get('/admin/affixes/create', ['as' => 'affixes.create', 'uses' => 'AffixesController@create']);
    Route::get('/admin/affixes/{affix}', ['as' => 'affixes.affix', 'uses' => 'AffixesController@show']);
    Route::get('/admin/affixes/{affix}/edit', ['as' => 'affixes.edit', 'uses' => 'AffixesController@edit']);
    Route::post('/admin/affixes/store', ['as' => 'affixes.store', 'uses' => 'AffixesController@store']);
    Route::post('/admin/affixes/{affix}/delete', ['as' => 'affixes.delete', 'uses' => 'AffixesController@delete']);

    Route::redirect('/admin/users', '/admin')->name('users.list');
    Route::get('/admin/user/{user}', ['as' => 'users.user', 'uses' => 'UsersController@show']);
    Route::post('/admin/user/{user}/silence-user', ['as' => 'user.silence', 'uses' => 'UsersController@silenceUser']);
    Route::post('/admin/users/{user}/ban-user', ['as' => 'ban.user', 'uses' => 'UsersController@banUser']);
    Route::post('/admin/users/{user}/un-ban-user', ['as' => 'unban.user', 'uses' => 'UsersController@unBanUser']);
    Route::post('/admin/users/{user}/ignore-unban-request', ['as' => 'user.ignore.unban.request', 'uses' => 'UsersController@ignoreUnBanRequest']);
    Route::post('/admin/users/{user}/force-name-change', ['as' => 'user.force.name.change', 'uses' => 'UsersController@forceNameChange']);

    Route::post('/admin/guide-quests/store', ['as' => 'admin.guide-quests.store', 'uses' => 'GuideQuestsController@store']);
    Route::post('/admin/guide-quests/{guideQuest}/delete', ['as' => 'admin.guide-quests.delete', 'uses' => 'GuideQuestsController@delete']);

    Route::post('/admin/guide-quests/export-data', ['as' => 'admin.guide-quests.export-data', 'uses' => 'GuideQuestsController@export']);
    Route::post('/admin/guide-quests/import-data', ['as' => 'admin.guide-quests.import-data', 'uses' => 'GuideQuestsController@import']);

    Route::get('/admin/guide-quests', ['as' => 'admin.guide-quests', 'uses' => 'GuideQuestsController@index']);
    Route::get('/admin/guide-quests/create', ['as' => 'admin.guide-quests.create', 'uses' => 'GuideQuestsController@create']);
    Route::get('/admin/guide-quests/edit/{guideQuest}', ['as' => 'admin.guide-quests.edit', 'uses' => 'GuideQuestsController@edit']);
    Route::get('/admin/guide-quests/show/{guideQuest}', ['as' => 'admin.guide-quests.show', 'uses' => 'GuideQuestsController@show']);
    Route::get('/admin/guide-quests/export', ['as' => 'admin.guide-quests.export', 'uses' => 'GuideQuestsController@exportGuideQuests']);
    Route::get('/admin/guide-quests/import', ['as' => 'admin.guide-quests.import', 'uses' => 'GuideQuestsController@importGuideQuests']);

    Route::post('/admin/information-management/export', ['as' => 'admin.info-management.export', 'uses' => 'InformationController@export']);
    Route::post('/admin/information-management/import', ['as' => 'admin.info-management.import', 'uses' => 'InformationController@import']);

    Route::redirect('/admin/information-management', '/admin')->name('admin.info-management');
    Route::get('/admin/information-management/export-data', ['as' => 'admin.info-management.export-data', 'uses' => 'InformationController@exportInfo']);
    Route::get('/admin/information-management/import-data', ['as' => 'admin.info-management.import-data', 'uses' => 'InformationController@importInfo']);
    Route::get('/admin/information-management/create-page', ['as' => 'admin.info-management.create-page', 'uses' => 'InformationController@managePage']);
    Route::get('/admin/information-management/page/{infoPage}', ['as' => 'admin.info-management.page', 'uses' => 'InformationController@page']);
    Route::get('/admin/information-management/update-page/{infoPage}', ['as' => 'admin.info-management.up-page', 'uses' => 'InformationController@managePage']);

    Route::post('/admin/raids/export', ['as' => 'admin.raids.export', 'uses' => 'RaidsController@export']);
    Route::post('/admin/raid/import', ['as' => 'admin.raids.import', 'uses' => 'RaidsController@import']);

    Route::redirect('/admin/raids', '/admin')->name('admin.raids.list');
    Route::get('/admin/raids/export-data', ['as' => 'admin.raids.export-data', 'uses' => 'RaidsController@exportRaids']);
    Route::get('/admin/raids/import-data', ['as' => 'admin.raids.import-data', 'uses' => 'RaidsController@importRaids']);
    Route::get('/admin/raids/create', ['as' => 'admin.raids.create', 'uses' => 'RaidsController@create']);
    Route::get('/admin/raids/{raid}/edit', ['as' => 'admin.raids.edit', 'uses' => 'RaidsController@edit']);
    Route::get('/admin/raids/{raid}', ['as' => 'admin.raids.show', 'uses' => 'RaidsController@show']);
    Route::post('/admin/raids/store', ['as' => 'admin.raids.store', 'uses' => 'RaidsController@store']);

    Route::post('/admin/item-skills/export', ['as' => 'admin.items-skills.export', 'uses' => 'ItemSkillsController@export']);
    Route::post('/admin/item-skills/import', ['as' => 'admin.items-skills.import', 'uses' => 'ItemSkillsController@import']);

    Route::redirect('/admin/item-skills', '/admin')->name('admin.items-skills.list');
    Route::get('/admin/item-skills/export-data', ['as' => 'admin.items-skills.export-data', 'uses' => 'ItemSkillsController@exportItemSkills']);
    Route::get('/admin/item-skills/import-data', ['as' => 'admin.items-skills.import-data', 'uses' => 'ItemSkillsController@importItemSkills']);
    Route::get('/admin/item-skills/create', ['as' => 'admin.items-skills.create', 'uses' => 'ItemSkillsController@create']);
    Route::get('/admin/item-skills/{itemSkill}/edit', ['as' => 'admin.items-skills.edit', 'uses' => 'ItemSkillsController@edit']);
    Route::get('/admin/item-skills/{itemSkill}', ['as' => 'admin.items-skills.show', 'uses' => 'ItemSkillsController@show']);
    Route::post('/admin/item-skills/store', ['as' => 'admin.item-skills.store', 'uses' => 'ItemSkillsController@store']);

    Route::get('/admin/statistics/dashboard', ['as' => 'admin.statistics', 'uses' => 'StatisticsController@index']);
    Route::get('/admin/events', ['as' => 'admin.events', 'uses' => 'EventScheduleController@index']);

    Route::redirect('/admin/feedback/bugs', '/admin')->name('admin.feedback.bugs');
    Route::get('/admin/feedback/bug/{bug}', ['as' => 'admin.feedback.bug', 'uses' => 'FeedbackController@bug']);
    Route::redirect('/admin/feedback/suggestions', '/admin')->name('admin.feedback.suggestions');
    Route::get('/admin/feedback/suggestion/{suggestion}', ['as' => 'admin.feedback.suggestion', 'uses' => 'FeedbackController@suggestion']);
    Route::post('/admin/feedback/delete/{feedbackId}', ['as' => 'admin.feedback.delete', 'uses' => 'FeedbackController@deleteFeedback']);

});
