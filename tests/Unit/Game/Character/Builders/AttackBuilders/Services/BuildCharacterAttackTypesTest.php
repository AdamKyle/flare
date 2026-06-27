<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders\Services;

use App\Flare\Models\Character;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateItem;

class BuildCharacterAttackTypesTest extends TestCase
{
    use CreateGameClassSpecial, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?BuildCharacterAttackTypes $buildCharacterAttackTypes;

    protected function setUp(): void
    {
        $this->useMockForAttackDataCache = false;

        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->buildCharacterAttackTypes = resolve(BuildCharacterAttackTypes::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->buildCharacterAttackTypes = null;
    }

    private function setUpCharacterForTests(): Character
    {
        $item = $this->createItem([
            'type' => WeaponTypes::STAVE,
            'base_damage' => 10,
        ]);

        $spellDamage = $this->createItem([
            'type' => SpellTypes::DAMAGE,
            'base_damage' => 10,
        ]);

        return $this->character->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->giveItem($spellDamage, true, 'spell-one')
            ->getCharacter();
    }

    public function test_build_character_attack_types_data()
    {
        $character = $this->setUpCharacterForTests();

        Cache::delete('character-attack-data-'.$character->id);

        $this->buildCharacterAttackTypes->buildCache($character);

        $this->assertNotNull(
            Cache::get('character-attack-data-'.$character->id)
        );
    }

    public function test_build_character_attack_types_calculates_damage_stat_amount_fresh_before_building()
    {
        $character = $this->setUpCharacterForTests();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 100,
            'increase_specialty_damage_per_level' => 0,
            'specialty_damage_uses_damage_stat_amount' => 1.0,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        Cache::put('character-attack-data-'.$character->id, [
            'damage_stat_amount' => 999999,
            'attack_types' => [],
        ]);

        $liveStat = $character->getInformation()->statMod($character->damage_stat);
        $expectedDamage = 100 + $liveStat * 1.0;

        $cacheData = $this->buildCharacterAttackTypes->buildCache($character);

        $this->assertEquals($liveStat, $cacheData['damage_stat_amount']);
        $this->assertNotEquals(999999, $cacheData['damage_stat_amount']);
        $this->assertEquals($expectedDamage, $cacheData['attack_types']['attack']['special_damage']['damage']);
    }

    public function test_attack_cache_rebuild_updates_class_specialty_damage_after_character_stat_changes()
    {
        $character = $this->setUpCharacterForTests();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 100,
            'increase_specialty_damage_per_level' => 0,
            'specialty_damage_uses_damage_stat_amount' => 1.0,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $cacheData1 = $this->buildCharacterAttackTypes->buildCache($character);
        $initialDamage = $cacheData1['attack_types']['attack']['special_damage']['damage'];

        $damageStat = $character->damage_stat;
        $character->update([
            $damageStat => $character->{$damageStat} + 1000,
        ]);
        $character = $character->refresh();

        Cache::put('character-attack-data-'.$character->id, [
            'damage_stat_amount' => 1,
            'attack_types' => [],
        ]);

        $cacheData2 = $this->buildCharacterAttackTypes->buildCache($character);
        $updatedDamage = $cacheData2['attack_types']['attack']['special_damage']['damage'];

        $this->assertGreaterThan($initialDamage, $updatedDamage);
        $this->assertEquals(100 + $character->getInformation()->statMod($character->damage_stat), $updatedDamage);
        $this->assertNotEquals(101, $updatedDamage);
    }
}
