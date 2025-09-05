<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassRankWeaponMastery;
use App\Flare\Models\GameClass;
use App\Game\Character\CharacterCreation\Pipeline\Steps\ClassRankAssigner;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class ClassRankAssignerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRace,
        CreateClass,
        CreateCharacter;

    public function testCreatesClassRanksAndWeaponMasteriesForAllClasses(): void
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

    public function testNoOpWhenStateHasNoCharacter(): void
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
