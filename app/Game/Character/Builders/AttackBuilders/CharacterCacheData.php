<?php

namespace App\Game\Character\Builders\AttackBuilders;

use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterAttackDataTransformer;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class CharacterCacheData
{

    public function __construct(private Manager $manager, private CharacterAttackDataTransformer $characterAttackDataTransformer, private CharacterStatBuilder $characterStatBuilder) {}

    public function setCharacterDefendAc(Character $character, int $defence)
    {
        Cache::put('character-defence-' . $character->id, $defence);
    }

    public function getCharacterDefenceAc(Character $character)
    {
        return Cache::get('character-defence-' . $character->id);
    }

    public function getDataFromAttackCache(Character $character, string $attackType): array
    {
        $characterAttackData = Cache::get('character-attack-data-' . $character->id);

        return $characterAttackData['attack_types'][$attackType];
    }

    public function getCachedCharacterData(Character $character, string $key): mixed
    {
        $cache = Cache::get('character-sheet-' . $character->id);

        if (is_null($cache)) {
            $cache = $this->characterSheetCache($character);
        } else {
            $cacheLevel = (int) str_replace(',', '', $cache['level']);

            if ($cacheLevel != $character->level) {
                $cache = $this->characterSheetCache($character);
            }
        }

        return $cache[$key];
    }

    public function deleteCharacterSheet(Character $character)
    {
        Cache::delete('character-defence-' . $character->id);

        if (Cache::has('character-sheet-' . $character->id)) {
            Cache::delete('character-sheet-' . $character->id);
        }
    }

    public function getCharacterSheetCache(Character $character): array
    {
        if (Cache::has('character-sheet-' . $character->id)) {
            return Cache::get('character-sheet-' . $character->id);
        }

        return $this->characterSheetCache($character);
    }

    public function updateCharacterSheetCache(Character $character, array $data)
    {
        if (Cache::has('character-sheet-' . $character->id)) {
            return Cache::put('character-sheet-' . $character->id, $data);
        }

        $this->characterSheetCache($character);

        $this->updateCharacterSheetCache($character, $data);
    }

    public function characterSheetCache(Character $character, bool $ignoreReductions = false): array
    {
        Cache::delete('character-defence-' . $character->id);

        $characterId = $character->id;

        $this->characterAttackDataTransformer->setIgnoreReductions($ignoreReductions);

        $characterSheet = new Item($character, $this->characterAttackDataTransformer);
        $characterSheet = $this->manager->createData($characterSheet)->toArray();

        $characterStatBuilder = $this->characterStatBuilder->setCharacter($character);

        $characterSheet['stat_affixes'] = [
            'cant_be_resisted' => $characterStatBuilder->canAffixesBeResisted(),
            'all_stat_reduction' => $characterStatBuilder->getStatReducingPrefix(),
            'stat_reduction' => $characterStatBuilder->getStatReducingSuffixes(),
        ];

        $skills = $character->skills;

        $characterSheet['skills'] = [
            'accuracy' => $skills->where('name', 'Accuracy')->first()->skill_bonus,
            'casting_accuracy' => $skills->where('name', 'Casting Accuracy')->first()->skill_bonus,
            'dodge' => $skills->where('name', 'Dodge')->first()->skill_bonus,
            'criticality' => $skills->where('name', 'Criticality')->first()->skill_bonus,
        ];

        $characterSheet['elemental_atonement'] = $this->characterStatBuilder->buildElementalAtonement();

        $characterSheet['weapon_attack'] = $this->characterStatBuilder->buildDamage('weapon');
        $characterSheet['spell_attack'] = $this->characterStatBuilder->buildDamage('spell-damage');
        $characterSheet['heal_for'] = $this->characterStatBuilder->buildHealing();

        Cache::put('character-sheet-' . $characterId, $characterSheet);

        return $characterSheet;
    }
}
