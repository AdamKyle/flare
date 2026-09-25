<?php

namespace App\Game\Character\Builders\AttackBuilders;

use App\Flare\Models\Character;
use App\Flare\Models\ItemAffix;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\CharacterAttack\Transformers\CharacterAttackDataTransformer;
use App\Game\Core\Items\Values\ItemType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Throwable;

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
     * Store the Character's defence AC in the cache.
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
     * Return the Character's cached defence AC.
     *
     * @param Character $character
     * @return mixed
     */
    public function getCharacterDefenceAc(Character $character): mixed
    {
        return Cache::get('character-defence-'.$character->id);
    }

    /**
     * Return the cached attack data for one attack type, such as attack, cast, defend, or one of their voided variants.
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
     * Return one value from the Character sheet cache, rebuilding the sheet when it is missing, unreadable, or built for another level.
     *
     * @param Character $character
     * @param string $key
     * @return mixed
     */
    public function getCachedCharacterData(Character $character, string $key): mixed
    {
        $cache = $this->readCharacterSheetCache($character);

        if (is_null($cache) || ! $this->isCharacterSheetForCurrentLevel($cache, $character)) {
            $cache = $this->characterSheetCache($character);
        }

        return $cache[$key];
    }

    /**
     * Delete the Character's cached defence AC and cached character sheet.
     *
     * @param Character $character
     * @return void
     */
    public function deleteCharacterSheet(Character $character): void
    {
        Cache::delete('character-defence-'.$character->id);
        Cache::delete('character-sheet-'.$character->id);
    }

    /**
     * Return the Character sheet cache, rebuilding it when it is missing or unreadable.
     *
     * @param Character $character
     * @return array
     */
    public function getCharacterSheetCache(Character $character): array
    {
        $cache = $this->readCharacterSheetCache($character);

        if (! is_null($cache)) {
            return $cache;
        }

        return $this->characterSheetCache($character);
    }

    /**
     * Replace the Character sheet cache with the supplied data, building the sheet first when no readable sheet exists.
     *
     * @param Character $character
     * @param array $data
     * @return bool
     */
    public function updateCharacterSheetCache(Character $character, array $data): bool
    {
        if (is_null($this->readCharacterSheetCache($character))) {
            $this->characterSheetCache($character);
        }

        return Cache::put('character-sheet-'.$character->id, $data);
    }

    /**
     * Build the Character sheet from authoritative data and store it in the cache.
     *
     * @param Character $character
     * @param bool $ignoreReductions
     * @return array
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

        $statReducingPrefix = $characterStatBuilder->getStatReducingPrefix();

        $characterSheet['stat_affixes'] = [
            'cant_be_resisted' => $characterStatBuilder->canAffixesBeResisted(),
            'all_stat_reduction' => is_null($statReducingPrefix) ? null : $this->statReductionSnapshot($statReducingPrefix),
            'stat_reduction' => array_map(
                fn (ItemAffix $affix): array => $this->statReductionSnapshot($affix),
                $characterStatBuilder->getStatReducingSuffixes(),
            ),
        ];

        $skills = $character->skills;

        $characterSheet['skills'] = [
            'accuracy' => $skills->where('name', 'Accuracy')->first()->skill_bonus,
            'casting_accuracy' => $skills->where('name', 'Casting Accuracy')->first()->skill_bonus,
            'dodge' => $skills->where('name', 'Dodge')->first()->skill_bonus,
            'criticality' => $skills->where('name', 'Criticality')->first()->skill_bonus,
        ];

        $characterSheet['elemental_atonement'] = $this->characterStatBuilder->buildElementalAtonement();

        $characterSheet['weapon_attack'] = $this->characterStatBuilder->buildDamage(ItemType::validWeapons());
        $characterSheet['spell_attack'] = $this->characterStatBuilder->buildDamage('spell-damage');
        $characterSheet['heal_for'] = $this->characterStatBuilder->buildHealing();

        Cache::put('character-sheet-'.$characterId, $characterSheet);

        return $characterSheet;
    }

    /**
     * Read the Character sheet cache, discarding an entry that cannot be read or deserialized so the caller rebuilds it from authoritative data.
     *
     * @param Character $character
     * @return ?array
     */
    private function readCharacterSheetCache(Character $character): ?array
    {
        $cacheKey = 'character-sheet-'.$character->id;

        try {
            $cache = Cache::get($cacheKey);
        } catch (Throwable $throwable) {
            Log::warning('Discarded an unreadable Character sheet cache entry.', [
                'character_id' => $character->id,
                'cache_key' => $cacheKey,
                'exception_class' => $throwable::class,
                'exception_message' => $throwable->getMessage(),
            ]);

            Cache::forget($cacheKey);

            return null;
        }

        return is_array($cache) ? $cache : null;
    }

    /**
     * Determine whether a cached Character sheet was built for the Character's current level.
     *
     * @param array $cache
     * @param Character $character
     * @return bool
     */
    private function isCharacterSheetForCurrentLevel(array $cache, Character $character): bool
    {
        $cacheLevel = filter_var(str_replace(',', '', $cache['level']), FILTER_VALIDATE_INT);

        return $cacheLevel === $character->level;
    }

    /**
     * Reduce a stat reducing Item Affix down to the plain scalar reduction fields consumed by battle setup, so no Eloquent model crosses the cache boundary.
     *
     * @param ItemAffix $affix
     * @return array
     */
    private function statReductionSnapshot(ItemAffix $affix): array
    {
        return [
            'str_reduction' => $affix->str_reduction,
            'dur_reduction' => $affix->dur_reduction,
            'dex_reduction' => $affix->dex_reduction,
            'chr_reduction' => $affix->chr_reduction,
            'int_reduction' => $affix->int_reduction,
            'agi_reduction' => $affix->agi_reduction,
            'focus_reduction' => $affix->focus_reduction,
        ];
    }
}
