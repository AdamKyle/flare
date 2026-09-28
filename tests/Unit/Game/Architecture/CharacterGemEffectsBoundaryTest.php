<?php

arch('external modules do not depend on the character Gem effect implementation')
    ->expect([
        'App\Game\Battle',
        'App\Game\BattleRewardProcessing',
        'App\Game\Character',
        'App\Game\ClassRanks',
        'App\Game\Skills',
    ])
    ->not->toUse('App\Game\Gems\Services\CharacterGemEffectService');

arch('character Gem effect consumers use the public contract')
    ->expect([
        'App\Game\BattleRewardProcessing\Services\CharacterCurrencyRewardService',
        'App\Game\BattleRewardProcessing\Services\CharacterXPService',
        'App\Game\Character\Builders\AttackBuilders\AttackDetails\CharacterAttackBuilder',
        'App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes',
        'App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder',
        'App\Game\ClassRanks\Services\ClassRankService',
        'App\Game\Skills\Services\SkillService',
    ])
    ->toUse('App\Game\Gems\Contracts\CharacterGemEffects');

arch('character Gem contracts do not depend on consuming modules')
    ->expect('App\Game\Gems\Contracts')
    ->not->toUse([
        'App\Flare\Models',
        'App\Game\Battle',
        'App\Game\BattleRewardProcessing',
        'App\Game\ClassRanks',
        'App\Game\Skills',
    ]);
