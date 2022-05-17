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

    public function getDataFromAttackCache(Character $character, string $attackType): array {
        $characterAttackData = Cache::get('character-attack-data-' . $character->id);

        return $characterAttackData['attack_types'][$attackType];
    }

    public function getCachedCharacterData(Character $character, string $key): mixed {
        if (Cache::has('character-sheet-' . $character->id)) {
            return Cache::get('character-sheet-' . $character->id)[$key];
        }

        $characterId = $character->id;

        $character = new Item($character, $this->characterSheetBaseInfoTransformer);
        $character = $this->manager->createData($character)->toArray();

        Cache::put('character-sheet-' . $characterId, $character);

        return $character[$key];
    }

    public function deleteCharacterSheet(Character $character) {
        if (Cache::has('character-sheet-' . $character->id)) {
            return Cache::delete('character-sheet-' . $character->id);
        }
    }
}
