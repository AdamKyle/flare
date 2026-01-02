<?php

namespace App\Game\Character\Builders\AttackBuilders;

use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterAttackDataTransformer;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Psr\SimpleCache\InvalidArgumentException;

class CharacterCacheData
{
    /**
     * @param Manager $manager
     * @param PlainDataSerializer $plainDataSerializer
     * @param CharacterAttackDataTransformer $characterAttackDataTransformer
     * @param CharacterStatBuilder $characterStatBuilder
     */
    public function __construct(
        private readonly Manager $manager,
        private readonly PlainDataSerializer $plainDataSerializer,
        private readonly CharacterAttackDataTransformer $characterAttackDataTransformer,
        private readonly CharacterStatBuilder $characterStatBuilder
    ) {}

    /**
     * Set the characters AC.
     *
     * @param Character $character
     * @param int $defence
     * @return void
     */
    public function setCharacterDefendAc(Character $character, int $defence): void
    {
        Cache::put('character-defence-'.$character->id, $defence);
    }

    /**
     * Get the characters Defence AC
     *
     * @param Character $character
     * @return mixed
     */
    public function getCharacterDefenceAc(Character $character): mixed
    {
        return Cache::get('character-defence-'.$character->id);
    }

    /**
     * Get specific data from the attack types based on the type.
     *
     * - attack
     * - voided_attack
     * - attack_and_cast
     * - voided_attack_and_cast
     * - cast
     * - voided_cast
     * - cast_and_attack
     * - voided_cast_and_attack
     * - defend
     * - voided_defend
     * - elemental_atonement
     *
     * @param Character $character
     * @param string $attackType
     * @return array
     */
    public function getDataFromAttackCache(Character $character, string $attackType): array
    {
        $characterAttackData = Cache::get('character-attack-data-'.$character->id);

        return $characterAttackData['attack_types'][$attackType];
    }

    /**
     * Gets a cached version of the character data.
     *
     * @param Character $character
     * @param string $key
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getCachedCharacterData(Character $character, string $key): mixed
    {
        $cache = Cache::get('character-sheet-'.$character->id);

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

    /**
     * Deletes the cached version of the character sheet
     *
     * @param Character $character
     * @return void
     * @throws InvalidArgumentException
     */
    public function deleteCharacterSheet(Character $character)
    {
        Cache::delete('character-defence-'.$character->id);

        if (Cache::has('character-sheet-'.$character->id)) {
            Cache::delete('character-sheet-'.$character->id);
        }
    }

    /**
     * Gets the character sheet data.
     *
     * @param Character $character
     * @return array
     * @throws InvalidArgumentException
     */
    public function getCharacterSheetCache(Character $character): array
    {
        if (Cache::has('character-sheet-'.$character->id)) {
            return Cache::get('character-sheet-'.$character->id);
        }

        return $this->characterSheetCache($character);
    }

    /**
     * Updates the character sheet cached data,
     *
     * @param Character $character
     * @param array $data
     * @return bool|void
     * @throws InvalidArgumentException
     */
    public function updateCharacterSheetCache(Character $character, array $data)
    {
        if (Cache::has('character-sheet-'.$character->id)) {
            return Cache::put('character-sheet-'.$character->id, $data);
        }

        $this->characterSheetCache($character);

        $this->updateCharacterSheetCache($character, $data);
    }

    /**
     * Creates the character sheet cache.
     *
     * @param Character $character
     * @param bool $ignoreReductions
     * @return array
     * @throws InvalidArgumentException
     */
    public function characterSheetCache(Character $character, bool $ignoreReductions = false): array
    {
        Cache::delete('character-defence-'.$character->id);

        $characterId = $character->id;

        $this->characterAttackDataTransformer->setIgnoreReductions($ignoreReductions);

        $characterSheet = new Item($character, $this->characterAttackDataTransformer);

        $this->manager->setSerializer($this->plainDataSerializer);

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

        Cache::put('character-sheet-'.$characterId, $characterSheet);

        return $characterSheet;
    }
}
