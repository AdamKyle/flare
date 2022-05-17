<?php

namespace App\Flare\Builders\Character;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use Cache;
use App\Flare\Models\Character;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class CharacterCacheData {

    private CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer;

    private Manager $manager;

    public function __construct(Manager $manager, CharacterSheetBaseInfoTransformer $characterInformationBuilder) {
        $this->manager                           = $manager;
        $this->characterSheetBaseInfoTransformer = $characterInformationBuilder;
    }

    public function getDataFromAttackCache(Character $character, string $attackType, string $key = null): array {
        if ($key === 'stat_reduction') {
            return $this->getStatReduction($character);
        }

        $characterAttackData = Cache::get('character-attack-data-' . $character->id);

        return $characterAttackData[$attackType];
    }

    public function getCachedCharacterData(Character $character, string $key): mixed {
        if (Cache::has('character-exploration-' . $character->id)) {
            return Cache::get('character-exploration-' . $character->id);
        }

        $characterId = $character->id;

        $character = new Item($character, $this->characterSheetBaseInfoTransformer);
        $character = $this->manager->createData($character)->toArray();

        Cache::put('character-exploration-stat-reduction-affixes-' . $characterId, $character);

        return $character[$key];
    }
}
