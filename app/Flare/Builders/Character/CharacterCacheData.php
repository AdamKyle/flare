<?php

namespace App\Flare\Builders\Character;

use Illuminate\Support\Facades\Cache;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Models\Character;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class CharacterCacheData extends CharacterPvpCacheData {

    private CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer;

    private Manager $manager;

    public function __construct(Manager $manager, CharacterSheetBaseInfoTransformer $characterInformationBuilder) {
        $this->manager                           = $manager;
        $this->characterSheetBaseInfoTransformer = $characterInformationBuilder;
    }

    public function setCharacterDefendAc(Character $character, int $defence) {
        Cache::put('character-defence-' . $character->id, $defence);
    }

    public function getCharacterDefenceAc(Character $character) {
        return Cache::get('character-defence-' . $character->id);
    }

    public function getDataFromAttackCache(Character $character, string $attackType): array {
        $characterAttackData = Cache::get('character-attack-data-' . $character->id);

        return $characterAttackData['attack_types'][$attackType];
    }

    public function getCachedCharacterData(Character $character, string $key): mixed {
        if (Cache::has('character-sheet-' . $character->id)) {
            $cache      = Cache::get('character-sheet-' . $character->id);
            $cacheLevel = (int) str_replace(',', '', $cache['level']);

            if ($cacheLevel != $character->level) {

                $cache = $this->characterSheetCache($character);
            }
        } else {
            $cache = $this->characterSheetCache($character);
        }

        return $cache[$key];
    }

    public function deleteCharacterSheet(Character $character) {
        Cache::delete('character-defence-' . $character->id);

        if (Cache::has('character-sheet-' . $character->id)) {
            return Cache::delete('character-sheet-' . $character->id);
        }
    }

    public function getCharacterSheetCache(Character $character): array {
        if (Cache::has('character-sheet-' . $character->id)) {
            return Cache::get('character-sheet-' . $character->id);
        }

        return $this->characterSheetCache($character);
    }

    public function updateCharacterSheetCache(Character $character, array $data) {
        if (Cache::has('character-sheet-' . $character->id)) {
            return Cache::put('character-sheet-' . $character->id, $data);
        }

        // If the cache doesn't exist, create it, set it.
        $this->characterSheetCache($character);

        $this->updateCharacterSheetCache($character, $data);
    }

    public function characterSheetCache(Character $character, bool $ignoreReductions = false): array {
        $this->deleteCharacterSheet($character);

        $characterId = $character->id;

        $this->characterSheetBaseInfoTransformer->setIgnoreReductions($ignoreReductions);

        $character = new Item($character, $this->characterSheetBaseInfoTransformer);
        $character = $this->manager->createData($character)->toArray();

        Cache::put('character-sheet-' . $characterId, $character);

        return $character;
    }
}
