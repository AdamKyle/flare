<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use App\Flare\Values\AttackTypeValue;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\AttackDataCacheSetUp;

class AttackDataManagement
{
    private const ATTACK_TYPE_MAP = [
        'attack' => AttackTypeValue::ATTACK,
        'voided_attack' => AttackTypeValue::ATTACK,
        'cast' => AttackTypeValue::CAST,
        'voided_cast' => AttackTypeValue::CAST,
        'cast_and_attack' => AttackTypeValue::CAST_AND_ATTACK,
        'voided_cast_and_attack' => AttackTypeValue::CAST_AND_ATTACK,
        'attack_and_cast' => AttackTypeValue::ATTACK_AND_CAST,
        'voided_attack_and_cast' => AttackTypeValue::ATTACK_AND_CAST,
        'defend' => AttackTypeValue::DEFEND,
        'voided_defend' => AttackTypeValue::DEFEND,
    ];

    private Character $character;

    private ?CharacterFactory $characterFactory;

    private AttackDataCacheSetUp $attackDataCacheSetUp;

    private array $attackData = [];

    /**
     * Constructor
     */
    public function __construct(Character $character, ?CharacterFactory $characterFactory = null)
    {
        $this->character = $character;
        $this->characterFactory = $characterFactory;
        $this->attackDataCacheSetUp = resolve(AttackDataCacheSetUp::class);
        $this->attackData = $this->attackDataCacheSetUp->getCacheObject();
    }

    /**
     * Creates deterministic attack data for tests.
     */
    public function setUpDeterministicAttackData(): AttackDataManagement
    {
        $this->attackData = $this->attackDataCacheSetUp->getCacheObject();

        foreach (self::ATTACK_TYPE_MAP as $attackTypeKey => $attackTypeValue) {
            $this->attackData['attack_types'][$attackTypeKey]['attack_type'] = $attackTypeValue;
            $this->attackData['attack_types'][$attackTypeKey]['special_damage'] = [];
            $this->attackData['attack_types'][$attackTypeKey]['ring_damage'] = 0;
            $this->attackData['attack_types'][$attackTypeKey]['artifact_damage'] = 0;
            $this->attackData['attack_types'][$attackTypeKey]['heal_for'] = 0;
            $this->attackData['attack_types'][$attackTypeKey]['res_chance'] = 0.0;
            $this->attackData['attack_types'][$attackTypeKey]['damage_deduction'] = 0.0;
            $this->attackData['attack_types'][$attackTypeKey]['affixes']['stacking_damage'] = 0;
            $this->attackData['attack_types'][$attackTypeKey]['affixes']['non_stacking_damage'] = 0;
            $this->attackData['attack_types'][$attackTypeKey]['affixes']['stacking_life_stealing'] = 0;
            $this->attackData['attack_types'][$attackTypeKey]['affixes']['life_stealing'] = 0;
            $this->attackData['attack_types'][$attackTypeKey]['affixes']['entrancing_chance'] = 0;
        }

        return $this->cacheAttackData();
    }

    /**
     * Set weapon damage for the supplied attack types.
     */
    public function setWeaponDamage(int|float $damage, array $attackTypeKeys = ['attack', 'voided_attack']): AttackDataManagement
    {
        foreach ($attackTypeKeys as $attackTypeKey) {
            $this->attackData['attack_types'][$attackTypeKey]['weapon_damage'] = $damage;
        }

        return $this->cacheAttackData();
    }

    /**
     * Set spell damage for the supplied attack types.
     */
    public function setSpellDamage(int|float $damage, array $attackTypeKeys = ['cast', 'voided_cast']): AttackDataManagement
    {
        foreach ($attackTypeKeys as $attackTypeKey) {
            $this->attackData['attack_types'][$attackTypeKey]['spell_damage'] = $damage;
        }

        return $this->cacheAttackData();
    }

    /**
     * Set values for one attack type.
     */
    public function setAttackTypeData(string $attackTypeKey, array $data): AttackDataManagement
    {
        $this->attackData['attack_types'][$attackTypeKey] = array_replace_recursive(
            $this->attackData['attack_types'][$attackTypeKey] ?? [],
            $data
        );

        return $this->cacheAttackData();
    }

    /**
     * Set character data values.
     */
    public function setCharacterData(array $data): AttackDataManagement
    {
        $this->attackData['character_data'] = array_replace_recursive(
            $this->attackData['character_data'],
            $data
        );

        return $this->cacheAttackData();
    }

    /**
     * Cache the attack data for the character.
     */
    public function cacheAttackData(): AttackDataManagement
    {
        Cache::put('character-attack-data-'.$this->character->id, $this->attackData);

        return $this;
    }

    /**
     * Get attack data.
     */
    public function getAttackData(): array
    {
        return $this->attackData;
    }

    /**
     * Get the character factory.
     */
    public function getCharacterFactory(): CharacterFactory
    {
        return $this->characterFactory;
    }

    /**
     * Get the character back.
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }
}
