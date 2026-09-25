<?php

arch('messages does not depend on the concrete raid identity query service')
    ->expect('App\Game\Messages')
    ->not->toUse('App\Game\Raids\Services\RaidIdentityQueryService');

arch('messages uses the raid identity contract')
    ->expect('App\Game\Messages')
    ->toUse('App\Game\Raids\Contracts\RaidIdentityQuery');

arch('raids contracts do not depend on raids implementation services')
    ->expect('App\Game\Raids\Contracts')
    ->not->toUse('App\Game\Raids\Services');

arch('raid identity does not depend on eloquent models')
    ->expect('App\Game\Raids\Values\RaidIdentity')
    ->not->toUse('App\Flare\Models');
