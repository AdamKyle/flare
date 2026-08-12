<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassRankWeaponMastery;
use App\Flare\Models\GameClass;
use App\Game\Character\CharacterCreation\Pipeline\Steps\ClassRankAssigner;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\Core\Items\Values\ItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class ClassRankAssignerTest extends TestCase
{
    use CreateCharacter,
        CreateClass,
        CreateRace,
        CreateUser,
        RefreshDatabase;

    public function test_creates_class_ranks_and_weapon_masteries_for_all_classes(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();

        $fighter = $this->createClass(['name' => 'Fighter']);
        $mage = $this->createClass(['name' => 'Mage']);

        $character = $this->createCharacter([
            'damage_stat' => $fighter->damage_stat,
            'name' => Str::random(10),
            'user_id' => $user->id,
            'level' => 1,
            'xp' => 0,
            'can_attack' => true,
            'can_move' => true,
            'inventory_max' => 75,
            'gold' => 10,
            'game_class_id' => $fighter->id,
            'game_race_id' => $race->id,
        ]);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($fighter)
            ->setCharacter($character)
            ->setNow(now());

        $step = app(ClassRankAssigner::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $totalClasses = GameClass::query()->count();
        $this->assertSame($totalClasses, CharacterClassRank::query()->where('character_id', $character->id)->count());

        $weaponTypesCount = count(ItemType::allWeaponTypes());

        $ranks = Character::query()->with('classRanks')->find($character->id)->classRanks;
        $this->assertTrue($ranks->isNotEmpty());

        $ranks->each(function (CharacterClassRank $rank) use ($weaponTypesCount) {
            $this->assertSame(
                $weaponTypesCount,
                CharacterClassRankWeaponMastery::query()->where('character_class_rank_id', $rank->id)->count()
            );
        });

        $fighterRank = $ranks->firstWhere('game_class_id', $fighter->id);
        $this->assertNotNull($fighterRank);

        $mapping = ItemTypeMapping::getForClass($fighter->name);
        $primaryType = is_array($mapping) ? $mapping[0] : $mapping;

        $fighterPrimary = CharacterClassRankWeaponMastery::query()
            ->where('character_class_rank_id', $fighterRank->id)
            ->where('weapon_type', $primaryType)
            ->first();

        $this->assertNotNull($fighterPrimary);
        $this->assertSame(5, (int) $fighterPrimary->level);
    }

    public function test_grants_prisoner_full_level_only_for_the_first_mapped_type(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $prisoner = $this->createClass(['name' => 'Prisoner']);

        $character = $this->createCharacter([
            'damage_stat' => $prisoner->damage_stat,
            'name' => Str::random(10),
            'user_id' => $user->id,
            'level' => 1,
            'xp' => 0,
            'can_attack' => true,
            'can_move' => true,
            'inventory_max' => 75,
            'gold' => 10,
            'game_class_id' => $prisoner->id,
            'game_race_id' => $race->id,
        ]);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($prisoner)
            ->setCharacter($character)
            ->setNow(now());

        app(ClassRankAssigner::class)->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $rank = CharacterClassRank::query()->where('character_id', $character->id)->where('game_class_id', $prisoner->id)->first();

        $this->assertSame(5, (int) CharacterClassRankWeaponMastery::query()->where('character_class_rank_id', $rank->id)->where('weapon_type', 'weapon')->first()->level);
        $this->assertSame(0, (int) CharacterClassRankWeaponMastery::query()->where('character_class_rank_id', $rank->id)->where('weapon_type', 'stave')->first()->level);
    }

    public function test_grants_merchant_specific_levels_for_mapped_types(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $merchant = $this->createClass(['name' => 'Merchant']);

        $character = $this->createCharacter([
            'damage_stat' => $merchant->damage_stat,
            'name' => Str::random(10),
            'user_id' => $user->id,
            'level' => 1,
            'xp' => 0,
            'can_attack' => true,
            'can_move' => true,
            'inventory_max' => 75,
            'gold' => 10,
            'game_class_id' => $merchant->id,
            'game_race_id' => $race->id,
        ]);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($merchant)
            ->setCharacter($character)
            ->setNow(now());

        app(ClassRankAssigner::class)->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $rank = CharacterClassRank::query()->where('character_id', $character->id)->where('game_class_id', $merchant->id)->first();

        $this->assertSame(2, (int) CharacterClassRankWeaponMastery::query()->where('character_class_rank_id', $rank->id)->where('weapon_type', 'bow')->first()->level);
        $this->assertSame(3, (int) CharacterClassRankWeaponMastery::query()->where('character_class_rank_id', $rank->id)->where('weapon_type', 'stave')->first()->level);
    }

    public function test_grants_default_level_for_a_non_special_mapped_class(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $thief = $this->createClass(['name' => 'Thief']);

        $character = $this->createCharacter([
            'damage_stat' => $thief->damage_stat,
            'name' => Str::random(10),
            'user_id' => $user->id,
            'level' => 1,
            'xp' => 0,
            'can_attack' => true,
            'can_move' => true,
            'inventory_max' => 75,
            'gold' => 10,
            'game_class_id' => $thief->id,
            'game_race_id' => $race->id,
        ]);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($thief)
            ->setCharacter($character)
            ->setNow(now());

        app(ClassRankAssigner::class)->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $rank = CharacterClassRank::query()->where('character_id', $character->id)->where('game_class_id', $thief->id)->first();

        $this->assertSame(5, (int) CharacterClassRankWeaponMastery::query()->where('character_class_rank_id', $rank->id)->where('weapon_type', 'dagger')->first()->level);
        $this->assertSame(5, (int) CharacterClassRankWeaponMastery::query()->where('character_class_rank_id', $rank->id)->where('weapon_type', 'bow')->first()->level);
    }

    public function test_no_op_when_state_has_no_character(): void
    {
        $this->createClass(['name' => 'Fighter']);

        $state = app(CharacterBuildState::class)->setNow(now());

        $step = app(ClassRankAssigner::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);
        $this->assertSame(0, CharacterClassRank::query()->count());
        $this->assertSame(0, CharacterClassRankWeaponMastery::query()->count());
    }
}
