<?php

namespace App\Flare\Builders\Character;

use League\Fractal\Manager;
use App\Flare\Models\Character;
use League\Fractal\Resource\Item;
use Illuminate\Support\Facades\Cache;
use App\Flare\Transformers\CharacterAttackDataTransformer;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;

class CharacterCacheData extends CharacterPvpCacheData {

    private CharacterAttackDataTransformer $characterSheetBaseInfoTransformer;

    private Manager $manager;

    private CharacterStatBuilder $characterStatBuilder;

    public function __construct(Manager $manager, CharacterAttackDataTransformer $characterInformationBuilder, CharacterStatBuilder $characterStatBuilder) {
        $this->manager                           = $manager;
        $this->characterSheetBaseInfoTransformer = $characterInformationBuilder;
        $this->characterStatBuilder              = $characterStatBuilder;
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

        $characterSheet = new Item($character, $this->characterSheetBaseInfoTransformer);
        $characterSheet = $this->manager->createData($characterSheet)->toArray();

        $characterStatBuilder = $this->characterStatBuilder->setCharacter($character);

        $characterSheet['stat_affixes'] = [
            'cant_be_resisted'   => $characterStatBuilder->canAffixesBeResisted(),
            'all_stat_reduction' => $characterStatBuilder->getStatReducingPrefix(),
            'stat_reduction'     => $characterStatBuilder->getStatReducingSuffixes(),
        ];

        $skills = $character->skills;

        $characterSheet['skills'] = [
            'accuracy'         => $skills->where('name', 'Accuracy')->first()->skill_bonus,
            'casting_accuracy' => $skills->where('name', 'Casting Accuracy')->first()->skill_bonus,
            'dodge'            => $skills->where('name', 'Dodge')->first()->skill_bonus,
            'criticality'      => $skills->where('name', 'Criticality')->first()->skill_bonus,
        ];

        $characterSheet['elemental_atonement'] = $this->characterStatBuilder->buildElementalAtonement();

        $characterSheet['weapon_attack'] = $this->characterStatBuilder->buildDamage('weapon');

        Cache::put('character-sheet-' . $characterId, $characterSheet);

        return $characterSheet;
    }
}
