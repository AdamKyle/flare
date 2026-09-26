<?php

arch('the skill model does not depend on skills module services')
    ->expect('App\Flare\Models\Skill')
    ->not->toUse('App\Game\Skills\Services');

arch('battle reward processing does not depend on the concrete skill bonus services')
    ->expect('App\Game\BattleRewardProcessing')
    ->not->toUse(['App\Game\Skills\Services\SkillBonusService', 'App\Game\Skills\Services\SkillBonusContextService']);

arch('core does not depend on the concrete skill bonus services')
    ->expect('App\Game\Core')
    ->not->toUse(['App\Game\Skills\Services\SkillBonusService', 'App\Game\Skills\Services\SkillBonusContextService']);

arch('character does not depend on the concrete skill bonus services')
    ->expect('App\Game\Character')
    ->not->toUse(['App\Game\Skills\Services\SkillBonusService', 'App\Game\Skills\Services\SkillBonusContextService']);

arch('kingdoms does not depend on the concrete skill bonus services')
    ->expect('App\Game\Kingdoms')
    ->not->toUse(['App\Game\Skills\Services\SkillBonusService', 'App\Game\Skills\Services\SkillBonusContextService']);

arch('skill bonus consumers outside the skills module use the skill bonus contract')
    ->expect([
        'App\Game\BattleRewardProcessing\Services\BattleRewardService',
        'App\Game\Core\Services\DropCheckService',
        'App\Game\Character\Builders\AttackBuilders\CharacterCacheData',
        'App\Game\Kingdoms\Service\KingdomUpdateService',
    ])
    ->toUse('App\Game\Skills\Contracts\SkillBonusQuery');

arch('skills contracts do not depend on skills implementation services')
    ->expect('App\Game\Skills\Contracts')
    ->not->toUse('App\Game\Skills\Services');
