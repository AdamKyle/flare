<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function() {
    Route::get('/view/passive/{characterPassiveSkill}/{character}', ['as' => 'view.passive.skill', 'uses' => 'CharacterPassiveSkillController@viewSkill']);
    Route::get('/view/character-passive/{passiveSkill}/{character}', ['as' => 'view.character.passive.skill', 'uses' => 'CharacterPassiveSkillController@viewCharacterPassiveSkill']);
});
