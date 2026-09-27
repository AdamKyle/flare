<?php

arch('character inventory compensation cache path does not depend on kingdom implementation services')
    ->expect('App\Game\Character\CharacterInventory\Services\CharacterCurrencyCacheService')
    ->not->toUse('App\Game\Kingdoms\Service');

arch('character inventory compensation cache path uses the kingdom gold bar contract')
    ->expect('App\Game\Character\CharacterInventory\Services\CharacterCurrencyCacheService')
    ->toUse('App\Game\Kingdoms\Contracts\CharacterGoldBarDeposit');

arch('kingdom gold bar deposit contract does not depend on kingdom implementation services')
    ->expect('App\Game\Kingdoms\Contracts')
    ->not->toUse('App\Game\Kingdoms\Service');

arch('kingdom gold bar deposit contract does not expose eloquent models')
    ->expect('App\Game\Kingdoms\Contracts\CharacterGoldBarDeposit')
    ->not->toUse('App\Flare\Models');

arch('character inventory contracts do not depend on character inventory services')
    ->expect('App\Game\Character\CharacterInventory\Contracts')
    ->not->toUse('App\Game\Character\CharacterInventory\Services');
