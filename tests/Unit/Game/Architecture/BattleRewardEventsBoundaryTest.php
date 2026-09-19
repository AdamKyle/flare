<?php

arch('battle reward processing does not depend on the concrete global event participation service')
    ->expect('App\Game\BattleRewardProcessing')
    ->not->toUse('App\Game\Events\Services\BattleGlobalEventParticipationService');

arch('battle reward processing uses the global event participation contract')
    ->expect('App\Game\BattleRewardProcessing')
    ->toUse('App\Game\Events\Contracts\BattleGlobalEventParticipation');

arch('events contracts do not depend on events implementation services')
    ->expect('App\Game\Events\Contracts')
    ->not->toUse('App\Game\Events\Services');

arch('battle global event participation result does not depend on eloquent models')
    ->expect('App\Game\Events\Values\BattleGlobalEventParticipationResult')
    ->not->toUse('App\Flare\Models');
