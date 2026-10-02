<?php

arch('character inventory controllers do not depend on the gem ability duplicate implementation')
    ->expect('App\Game\Character\CharacterInventory\Controllers')
    ->not->toUse('App\Game\Gems\Services\GemAbilityDuplicateService');

arch('core items does not depend on gems services or transformers')
    ->expect('App\Game\Core\Items')
    ->not->toUse(['App\Game\Gems\Services', 'App\Game\Gems\Transformers']);

arch('skills gem crafting does not depend on gems services or transformers')
    ->expect('App\Game\Skills\Services\GemService')
    ->not->toUse(['App\Game\Gems\Services', 'App\Game\Gems\Transformers']);

arch('gem consumers depend on the gems public contracts')
    ->expect([
        'App\Game\Character\CharacterInventory\Controllers\Api\CharacterInventoryController',
        'App\Game\Core\Items\Transformers\ItemTransformer',
    ])
    ->toUse('App\Game\Gems\Contracts\GemAbilityDuplicateQuery');

arch('item sockets and gem crafting depend on the character gem details contract')
    ->expect([
        'App\Game\Core\Items\Transformers\ItemSocketTransformer',
        'App\Game\Skills\Services\GemService',
    ])
    ->toUse('App\Game\Gems\Contracts\CharacterGemDetails');

arch('gems contracts do not depend on gems implementation services')
    ->expect('App\Game\Gems\Contracts')
    ->not->toUse('App\Game\Gems\Services');
